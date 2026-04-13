<?php

namespace App\Http\Controllers\Instructor;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CourseController extends Controller
{
    /**
     * Display a listing of courses.
     */
    public function index()
    {
        $courses = Course::where('instructor_id', Auth::id())
            ->orderBy('created_at', 'desc')
            ->paginate(10);
        
        // Calculate statistics
        $allCourses = Course::where('instructor_id', Auth::id())->get();
        
        $stats = [
            'total_courses' => $allCourses->count(),
            'active_courses' => $allCourses->where('status', 'active')->count(),
            'inactive_courses' => $allCourses->where('status', 'inactive')->count(),
            'total_students' => $allCourses->sum(function($course) {
                return $course->students()->count();
            }),
        ];
        
        return view('instructor.courses.index', compact('courses', 'stats'));
    }

    /**
     * Show the form for creating a new course.
     */
    public function create()
    {
        return view('instructor.courses.create');
    }

    /**
     * Generate a unique course code
     */
    private function generateUniqueCode($requestedCode)
    {
        $originalCode = strtoupper(trim($requestedCode));
        $code = $originalCode;
        $counter = 1;
        
        while (Course::where('code', $code)->exists()) {
            $code = $originalCode . '-' . $counter;
            $counter++;
        }
        
        return $code;
    }

    /**
     * Check if course code already exists (AJAX)
     */
    public function checkCourseCode(Request $request)
    {
        $code = strtoupper(trim($request->code));
        $exists = Course::where('code', $code)->exists();
        
        return response()->json([
            'exists' => $exists,
            'code' => $code,
            'message' => $exists ? 'Course code already exists' : 'Course code is available'
        ]);
    }

    /**
     * Store a newly created course in storage.
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'class_code' => 'required|string|max:255',
                'class_name' => 'required|string|max:255',
                'code' => 'required|string|max:255',
                'name' => 'required|string|max:255',
                'schedule' => 'nullable|string|max:255',
                'status' => 'required|in:active,inactive',
                'description' => 'nullable|string',
                'student_ids' => 'nullable|array',
                'student_ids.*' => 'exists:users,id'
            ]);

            // Check if code already exists and generate unique one
            $originalCode = strtoupper(trim($validated['code']));
            $finalCode = $this->generateUniqueCode($originalCode);
            
            // Prepare course data
            $courseData = [
                'class_code' => $validated['class_code'],
                'class_name' => $validated['class_name'],
                'code' => $finalCode,
                'name' => $validated['name'],
                'schedule' => $validated['schedule'] ?? null,
                'status' => $validated['status'],
                'description' => $validated['description'] ?? null,
                'instructor_id' => Auth::id(),
            ];
            
            DB::beginTransaction();
            
            $course = Course::create($courseData);
            
            $enrolledCount = 0;
            if ($request->has('student_ids') && !empty($request->student_ids)) {
                $enrollmentData = [];
                foreach ($request->student_ids as $studentId) {
                    $enrollmentData[$studentId] = [
                        'enrolled_at' => now(),
                        'status' => 'active',
                    ];
                }
                $course->students()->attach($enrollmentData);
                $enrolledCount = count($request->student_ids);
            }
            
            DB::commit();
            
            // Build success message
            $message = "Course '{$validated['class_name']}' created successfully!";
            if ($finalCode !== $originalCode) {
                $message .= " Note: Course code was changed from '{$originalCode}' to '{$finalCode}' because it was already taken.";
            }
            if ($enrolledCount > 0) {
                $message .= " {$enrolledCount} student(s) enrolled.";
            }
            
            return redirect()->route('instructor.courses.index')
                ->with('success', $message);
                
        } catch (\Illuminate\Validation\ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()
                ->with('error', 'Error creating course: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified course.
     */
    public function show(Course $course)
    {
        if ($course->instructor_id !== Auth::id()) {
            abort(403, 'Unauthorized access');
        }
        
        // Get enrolled students with their grades
        $enrolledStudents = $course->students()->get();
        $totalStudents = $enrolledStudents->count();
        $studentsWithGrades = $enrolledStudents->filter(function($student) {
            return !is_null($student->pivot->final_grade);
        })->count();
        
        $averageGrade = $studentsWithGrades > 0 ? $enrolledStudents->avg(function($student) {
            return $student->pivot->final_grade;
        }) : 0;
        
        $passingRate = $studentsWithGrades > 0 ? ($enrolledStudents->filter(function($student) {
            return $student->pivot->final_grade <= 3.0;
        })->count() / $studentsWithGrades) * 100 : 0;
        
        return view('instructor.courses.show', compact('course', 'enrolledStudents', 'totalStudents', 'studentsWithGrades', 'averageGrade', 'passingRate'));
    }

    /**
     * Show the form for editing the specified course.
     */
    public function edit(Course $course)
    {
        if ($course->instructor_id !== Auth::id()) {
            abort(403, 'Unauthorized access');
        }
        
        return view('instructor.courses.edit', compact('course'));
    }

    /**
     * Update the specified course in storage.
     */
    public function update(Request $request, Course $course)
    {
        try {
            if ($course->instructor_id !== Auth::id()) {
                return back()->with('error', 'You are not authorized to update this course.');
            }

            $validated = $request->validate([
                'class_code' => 'required|string|max:255',
                'class_name' => 'required|string|max:255',
                'code' => 'required|string|max:255',
                'name' => 'required|string|max:255',
                'schedule' => 'nullable|string|max:255',
                'status' => 'required|in:active,inactive',
                'description' => 'nullable|string',
            ]);

            // Check if code is being changed and if the new code already exists
            $newCode = strtoupper(trim($validated['code']));
            if ($course->code !== $newCode) {
                $existingCourse = Course::where('code', $newCode)->first();
                if ($existingCourse) {
                    return back()->withInput()
                        ->with('error', "Course code '{$newCode}' is already taken. Please use a different code.");
                }
            }

            $course->update($validated);

            return redirect()->route('instructor.courses.index')
                ->with('success', 'Course updated successfully!');
                
        } catch (\Illuminate\Validation\ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        } catch (\Exception $e) {
            return back()->withInput()
                ->with('error', 'Error updating course: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified course from storage.
     */
    public function destroy(Course $course)
    {
        try {
            if ($course->instructor_id !== Auth::id()) {
                return back()->with('error', 'You are not authorized to delete this course.');
            }

            DB::beginTransaction();
            
            // Detach all students first
            $course->students()->detach();
            // Delete the course
            $course->delete();
            
            DB::commit();

            return redirect()->route('instructor.courses.index')
                ->with('success', "Course '{$course->class_name}' deleted successfully!");
                
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Error deleting course: ' . $e->getMessage());
        }
    }

    /**
     * Show form to add students to a course.
     */
    public function addStudents(Course $course)
    {
        if ($course->instructor_id !== Auth::id()) {
            abort(403, 'Unauthorized access');
        }
        
        $enrolledStudentIds = $course->students()->pluck('users.id')->toArray();
        
        $students = User::where('role', 'student')
            ->whereNotIn('id', $enrolledStudentIds)
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get();
        
        return view('instructor.courses.add-students', compact('course', 'students'));
    }

    /**
     * Enroll students to a course.
     */
    public function enrollStudents(Request $request, Course $course)
    {
        try {
            if ($course->instructor_id !== Auth::id()) {
                return back()->with('error', 'You are not authorized to enroll students in this course.');
            }

            $validated = $request->validate([
                'student_ids' => 'required|array',
                'student_ids.*' => 'exists:users,id',
            ]);
            
            $enrolledCount = 0;
            foreach ($validated['student_ids'] as $studentId) {
                if (!$course->students()->where('user_id', $studentId)->exists()) {
                    $course->students()->attach($studentId, [
                        'enrolled_at' => now(),
                        'status' => 'active',
                    ]);
                    $enrolledCount++;
                }
            }
            
            return redirect()->route('instructor.courses.show', $course->id)
                ->with('success', "$enrolledCount student(s) enrolled successfully!");
                
        } catch (\Exception $e) {
            return back()->with('error', 'Error enrolling students: ' . $e->getMessage());
        }
    }

    /**
     * Remove a student from a course.
     */
    public function removeStudent(Course $course, $studentId)
    {
        try {
            if ($course->instructor_id !== Auth::id()) {
                return back()->with('error', 'You are not authorized to remove students from this course.');
            }
            
            $course->students()->detach($studentId);
            
            return redirect()->route('instructor.courses.show', $course->id)
                ->with('success', 'Student removed from course successfully!');
                
        } catch (\Exception $e) {
            return back()->with('error', 'Error removing student: ' . $e->getMessage());
        }
    }

    /**
     * Bulk enroll students via CSV/Excel.
     */
    public function bulkEnroll(Request $request, Course $course)
    {
        try {
            if ($course->instructor_id !== Auth::id()) {
                return back()->with('error', 'You are not authorized to enroll students in this course.');
            }
            
            $request->validate([
                'student_ids' => 'required|string',
            ]);
            
            $studentIds = explode(',', $request->student_ids);
            $success = 0;
            $failed = [];
            
            foreach ($studentIds as $id) {
                $id = trim($id);
                $student = User::where('role', 'student')
                    ->where(function($q) use ($id) {
                        $q->where('id', $id)->orWhere('student_id', $id);
                    })->first();
                
                if ($student && !$course->students()->where('user_id', $student->id)->exists()) {
                    $course->students()->attach($student->id, [
                        'enrolled_at' => now(),
                        'status' => 'active',
                    ]);
                    $success++;
                } else {
                    $failed[] = $id;
                }
            }
            
            $msg = "$success student(s) enrolled successfully!";
            if (!empty($failed)) {
                $msg .= " Failed: " . implode(', ', $failed);
            }
            
            return redirect()->route('instructor.courses.show', $course->id)
                ->with('success', $msg);
                
        } catch (\Exception $e) {
            return back()->with('error', 'Error during bulk enrollment: ' . $e->getMessage());
        }
    }

    /**
     * Update student grade in a course.
     */
    public function updateGrade(Request $request, Course $course, $studentId)
    {
        try {
            if ($course->instructor_id !== Auth::id()) {
                return response()->json(['error' => 'Unauthorized'], 403);
            }
            
            $validated = $request->validate([
                'grade' => 'nullable|numeric|min:1.0|max:5.0',
            ]);
            
            $course->students()->updateExistingPivot($studentId, [
                'final_grade' => $validated['grade'] ?? null,
                'updated_at' => now(),
            ]);
            
            return response()->json(['success' => true]);
            
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}