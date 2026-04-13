<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class StudentController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        // Get students from both tables to ensure consistency
        $students = Student::orderBy('created_at', 'desc')->get();
        
        $studentData = $students->map(function($student) {
            return [
                'id' => $student->id,
                'student_id' => $student->student_id,
                'first_name' => $student->first_name,
                'middle_name' => $student->middle_name,
                'last_name' => $student->last_name,
                'fullname' => $student->full_name,
                'initial' => $student->initials,
                'avatar_color' => $student->avatar_color,
                'program' => $student->program ?? 'Not Assigned',
                'year_level' => $student->year_level ?? 'N/A',
                'status' => $student->status ?? 'Pending',
                'status_color' => $student->status_color,
                'email' => $student->email,
                'phone' => $student->phone ?? 'Not Provided',
                'created_at' => $student->created_at ? $student->created_at->format('M d, Y') : 'N/A',
            ];
        });

        $totalStudents = $students->count();
        $activeStudents = $students->where('status', 'Active')->count();
        $inactiveStudents = $students->where('status', 'Inactive')->count();
        $pendingStudents = $students->where('status', 'Pending')->count();
        
        $programs = Student::getPrograms();
        $yearLevels = Student::getYearLevels();
        $statuses = Student::getStatuses();

        return view('student', compact(
            'studentData',
            'totalStudents',
            'activeStudents',
            'inactiveStudents',
            'pendingStudents',
            'programs',
            'yearLevels',
            'statuses'
        ));
    }

    /**
     * Generate a unique student ID
     */
    private function generateStudentId()
    {
        $lastStudent = Student::orderBy('id', 'desc')->first();
        
        if ($lastStudent && $lastStudent->student_id) {
            // Extract the number from existing student ID (format: STU00001, BSU-2024-0001, etc.)
            preg_match('/\d+$/', $lastStudent->student_id, $matches);
            if (!empty($matches)) {
                $lastNumber = intval($matches[0]);
                $newNumber = $lastNumber + 1;
                // Keep the same format (STU + 5 digits)
                $studentId = 'STU' . str_pad($newNumber, 5, '0', STR_PAD_LEFT);
            } else {
                $studentId = 'STU00001';
            }
        } else {
            $studentId = 'STU00001';
        }
        
        // Check if the generated ID already exists (just in case)
        $existing = Student::where('student_id', $studentId)->first();
        if ($existing) {
            // If exists, generate a new one with timestamp
            $studentId = 'STU' . time();
        }
        
        return $studentId;
    }

    public function store(Request $request)
    {
        // Log the incoming request for debugging
        Log::info('Student creation attempt', $request->all());
        
        $validator = Validator::make($request->all(), [
            'first_name' => 'required|string|max:255',
            'middle_name' => 'nullable|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|unique:students,email|unique:users,email',
            'phone' => 'nullable|string|max:20',
            'program' => 'nullable|string|max:255',
            'year_level' => 'nullable|string|max:50',
            'status' => 'required|in:Active,Inactive,Pending,Graduated,Suspended',
        ]);

        if ($validator->fails()) {
            Log::error('Validation failed', $validator->errors()->toArray());
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            // Generate student ID
            $studentId = $this->generateStudentId();
            
            // Create full name
            $fullName = trim($request->first_name . ' ' . ($request->middle_name ? $request->middle_name . ' ' : '') . $request->last_name);
            
            // Generate random password (use student ID as default password)
            $defaultPassword = $studentId . '123';
            $hashedPassword = Hash::make($defaultPassword);
            
            // Create user account
            $user = User::create([
                'first_name' => $request->first_name,
                'middle_name' => $request->middle_name,
                'last_name' => $request->last_name,
                'name' => $fullName,
                'email' => $request->email,
                'phone' => $request->phone,
                'role' => 'student',
                'student_id' => $studentId,
                'program' => $request->program,
                'year_level' => $request->year_level,
                'status' => strtolower($request->status),
                'password' => $hashedPassword,
            ]);

            // Create student record
            $student = Student::create([
                'user_id' => $user->id,
                'student_id' => $studentId,
                'first_name' => $request->first_name,
                'middle_name' => $request->middle_name,
                'last_name' => $request->last_name,
                'name' => $fullName,
                'email' => $request->email,
                'phone' => $request->phone,
                'program' => $request->program,
                'year_level' => $request->year_level,
                'status' => $request->status,
            ]);

            Log::info('Student created successfully', [
                'student_id' => $studentId, 
                'user_id' => $user->id,
                'student_record_id' => $student->id
            ]);
            
            return redirect()->route('students.index')
                ->with('success', "Student added successfully! Student ID: {$studentId}, Password: {$defaultPassword}")
                ->with('info', 'Please provide the password to the student for login.');
            
        } catch (\Exception $e) {
            Log::error('Student creation failed: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return redirect()->back()
                ->with('error', 'Failed to add student: ' . $e->getMessage())
                ->withInput();
        }
    }

    public function show($id)
    {
        $student = Student::findOrFail($id);
        return response()->json($student);
    }

    public function edit($id)
    {
        $student = Student::findOrFail($id);
        return response()->json($student);
    }

    /**
     * Get student data for editing (AJAX)
     */
    public function getStudentForEdit($id)
    {
        try {
            // Try to find in students table first
            $student = Student::find($id);
            
            if (!$student) {
                // If not found, try to find in users table
                $user = User::where('role', 'student')->find($id);
                if ($user) {
                    return response()->json([
                        'id' => $user->id,
                        'first_name' => $user->first_name,
                        'middle_name' => $user->middle_name,
                        'last_name' => $user->last_name,
                        'email' => $user->email,
                        'phone' => $user->phone,
                        'program' => $user->program,
                        'year_level' => $user->year_level,
                        'status' => ucfirst($user->status ?? 'active'),
                        'student_id' => $user->student_id,
                        'full_name' => $user->name,
                    ]);
                }
                throw new \Exception('Student not found');
            }
            
            // Return all student data needed for the edit form
            return response()->json([
                'id' => $student->id,
                'first_name' => $student->first_name,
                'middle_name' => $student->middle_name,
                'last_name' => $student->last_name,
                'email' => $student->email,
                'phone' => $student->phone,
                'program' => $student->program,
                'year_level' => $student->year_level,
                'status' => $student->status,
                'student_id' => $student->student_id,
                'full_name' => $student->full_name,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to fetch student for edit: ' . $e->getMessage());
            return response()->json(['error' => 'Student not found'], 404);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $student = Student::findOrFail($id);
            
            $validator = Validator::make($request->all(), [
                'first_name' => 'required|string|max:255',
                'middle_name' => 'nullable|string|max:255',
                'last_name' => 'required|string|max:255',
                'email' => 'required|email|unique:students,email,' . $id . '|unique:users,email,' . ($student->user_id ?? 0),
                'phone' => 'nullable|string|max:20',
                'program' => 'nullable|string|max:255',
                'year_level' => 'nullable|string|max:50',
                'status' => 'required|in:Active,Inactive,Pending,Graduated,Suspended',
            ]);

            if ($validator->fails()) {
                return redirect()->back()
                    ->withErrors($validator)
                    ->withInput();
            }

            $fullName = trim($request->first_name . ' ' . ($request->middle_name ? $request->middle_name . ' ' : '') . $request->last_name);

            // Update user account if exists
            if ($student->user) {
                $student->user->update([
                    'first_name' => $request->first_name,
                    'middle_name' => $request->middle_name,
                    'last_name' => $request->last_name,
                    'name' => $fullName,
                    'email' => $request->email,
                    'phone' => $request->phone,
                    'program' => $request->program,
                    'year_level' => $request->year_level,
                    'status' => strtolower($request->status),
                ]);
            } else {
                // If no user exists, create one
                $defaultPassword = Hash::make($student->student_id . '123');
                $user = User::create([
                    'first_name' => $request->first_name,
                    'middle_name' => $request->middle_name,
                    'last_name' => $request->last_name,
                    'name' => $fullName,
                    'email' => $request->email,
                    'phone' => $request->phone,
                    'role' => 'student',
                    'student_id' => $student->student_id,
                    'program' => $request->program,
                    'year_level' => $request->year_level,
                    'status' => strtolower($request->status),
                    'password' => $defaultPassword,
                ]);
                $student->user_id = $user->id;
                $student->save();
            }

            // Update student record
            $student->update([
                'first_name' => $request->first_name,
                'middle_name' => $request->middle_name,
                'last_name' => $request->last_name,
                'name' => $fullName,
                'email' => $request->email,
                'phone' => $request->phone,
                'program' => $request->program,
                'year_level' => $request->year_level,
                'status' => $request->status,
            ]);

            return redirect()->route('students.index')
                ->with('success', 'Student updated successfully!');
            
        } catch (\Exception $e) {
            Log::error('Student update failed: ' . $e->getMessage());
            return redirect()->back()
                ->with('error', 'Failed to update student: ' . $e->getMessage())
                ->withInput();
        }
    }

    public function destroy($id)
    {
        try {
            $student = Student::findOrFail($id);
            $studentName = $student->full_name;
            
            // Delete associated user account
            if ($student->user) {
                $student->user->delete();
            }
            
            // Delete student record
            $student->delete();

            return redirect()->route('students.index')
                ->with('success', "Student {$studentName} has been deleted successfully!");
            
        } catch (\Exception $e) {
            Log::error('Student deletion failed: ' . $e->getMessage());
            return redirect()->back()
                ->with('error', 'Failed to delete student. Please try again.');
        }
    }
    
    /**
     * Sync all students from users table to students table
     * Run this to fix missing student records
     */
    public function syncStudents()
    {
        $users = User::where('role', 'student')->get();
        $synced = 0;
        
        foreach ($users as $user) {
            $existing = Student::where('user_id', $user->id)->first();
            if (!$existing) {
                Student::create([
                    'user_id' => $user->id,
                    'student_id' => $user->student_id ?? $this->generateStudentId(),
                    'first_name' => $user->first_name,
                    'middle_name' => $user->middle_name,
                    'last_name' => $user->last_name,
                    'name' => $user->name,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'program' => $user->program,
                    'year_level' => $user->year_level,
                    'status' => ucfirst($user->status ?? 'Active'),
                ]);
                $synced++;
            }
        }
        
        return redirect()->route('students.index')
            ->with('success', "Synced {$synced} students from users table.");
    }
}