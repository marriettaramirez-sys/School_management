@extends('layouts.instructor')

@section('title', 'Add New Student')

@section('content')
<div class="p-8 max-w-4xl mx-auto">
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900">Add New Student</h1>
        <p class="text-gray-600 mt-1">Create a new student account</p>
    </div>

    @if($errors->any())
    <div class="mb-6 bg-red-50 border border-red-200 rounded-2xl p-4">
        <div class="flex items-center gap-2 text-red-600 mb-2">
            <i class="fa-solid fa-exclamation-triangle"></i>
            <strong class="text-sm">Please fix the following errors:</strong>
        </div>
        <ul class="list-disc list-inside text-sm text-red-600">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    <form method="POST" action="{{ route('instructor.students.store') }}" class="bg-white rounded-2xl shadow-sm border border-gray-100 p-8">
        @csrf

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Student ID - Added Field -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Student ID *</label>
                <input type="text" 
                       name="student_id" 
                       value="{{ old('student_id') }}" 
                       placeholder="e.g., 001, 002, 2024001, STU00001"
                       class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition @error('student_id') border-red-500 @enderror">
                @error('student_id')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
                <p class="text-xs text-gray-500 mt-1">Leave blank to auto-generate (e.g., STU00001, STU00002, etc.)</p>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">First Name *</label>
                <input type="text" 
                       name="first_name" 
                       value="{{ old('first_name') }}" 
                       class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition @error('first_name') border-red-500 @enderror"
                       required>
                @error('first_name')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Middle Name</label>
                <input type="text" 
                       name="middle_name" 
                       value="{{ old('middle_name') }}" 
                       class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Last Name *</label>
                <input type="text" 
                       name="last_name" 
                       value="{{ old('last_name') }}" 
                       class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition @error('last_name') border-red-500 @enderror"
                       required>
                @error('last_name')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Email Address *</label>
                <input type="email" 
                       name="email" 
                       value="{{ old('email') }}" 
                       class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition @error('email') border-red-500 @enderror"
                       required>
                @error('email')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Phone Number</label>
                <input type="text" 
                       name="phone" 
                       value="{{ old('phone') }}" 
                       class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition">
                @error('phone')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Program / Course -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Program / Course *</label>
                <select name="program" 
                        class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition @error('program') border-red-500 @enderror"
                        required>
                    <option value="">Select Program</option>
                    <option value="Computer Science Program" {{ old('program') == 'Computer Science Program' ? 'selected' : '' }}>Computer Science Program</option>
                    <option value="Teacher Education Program" {{ old('program') == 'Teacher Education Program' ? 'selected' : '' }}>Teacher Education Program</option>
                    <option value="Nursing Program" {{ old('program') == 'Nursing Program' ? 'selected' : '' }}>Nursing Program</option>
                    <option value="Accountancy Program" {{ old('program') == 'Accountancy Program' ? 'selected' : '' }}>Accountancy Program</option>
                    <option value="Business Administration Program" {{ old('program') == 'Business Administration Program' ? 'selected' : '' }}>Business Administration Program</option>
                    <option value="Criminal Justice Education Program" {{ old('program') == 'Criminal Justice Education Program' ? 'selected' : '' }}>Criminal Justice Education Program</option>
                    <option value="Art and Science Program" {{ old('program') == 'Art and Science Program' ? 'selected' : '' }}>Art and Science Program</option>
                    <option value="Engineering and Technology Program" {{ old('program') == 'Engineering and Technology Program' ? 'selected' : '' }}>Engineering and Technology Program</option>
                </select>
                @error('program')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Year Level -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Year Level *</label>
                <select name="year_level" 
                        class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition @error('year_level') border-red-500 @enderror"
                        required>
                    <option value="">Select Year Level</option>
                    <option value="1st Year" {{ old('year_level') == '1st Year' ? 'selected' : '' }}>1st Year</option>
                    <option value="2nd Year" {{ old('year_level') == '2nd Year' ? 'selected' : '' }}>2nd Year</option>
                    <option value="3rd Year" {{ old('year_level') == '3rd Year' ? 'selected' : '' }}>3rd Year</option>
                    <option value="4th Year" {{ old('year_level') == '4th Year' ? 'selected' : '' }}>4th Year</option>
                </select>
                @error('year_level')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Status -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                <select name="status" 
                        class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition">
                    <option value="active" {{ old('status') == 'active' ? 'selected' : '' }}>Active</option>
                    <option value="inactive" {{ old('status') == 'inactive' ? 'selected' : '' }}>Inactive</option>
                    <option value="pending" {{ old('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                    <option value="graduated" {{ old('status') == 'graduated' ? 'selected' : '' }}>Graduated</option>
                    <option value="suspended" {{ old('status') == 'suspended' ? 'selected' : '' }}>Suspended</option>
                </select>
                @error('status')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <div class="mt-8">
            <div class="bg-blue-50 rounded-xl p-4 border border-blue-200">
                <div class="flex items-start gap-3">
                    <i class="fa-solid fa-info-circle text-blue-600 mt-0.5"></i>
                    <div>
                        <p class="text-sm font-medium text-blue-800">Important Information:</p>
                        <p class="text-sm text-blue-800 mt-1">
                            • <strong>Student ID:</strong> If left blank, it will be auto-generated sequentially (e.g., STU00001, STU00002, etc.)
                        </p>
                        <p class="text-sm text-blue-800 mt-1">
                            • <strong>Default Password:</strong> Student ID + "123" (e.g., STU00001123)
                        </p>
                        <p class="text-sm text-blue-800 mt-1">
                            • <strong>Status:</strong> New students are automatically set to "Active" if not specified
                        </p>
                        <p class="text-sm text-blue-800 mt-1">
                            • <strong>Program and Year Level:</strong> Required fields for proper student classification
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <div class="flex gap-3 mt-8">
            <button type="submit" class="flex-1 bg-indigo-600 hover:bg-indigo-700 text-white py-3 rounded-xl font-semibold transition flex items-center justify-center gap-2">
                <i class="fa-solid fa-save"></i>
                Create Student
            </button>
            <a href="{{ route('instructor.students.index') }}" 
               class="flex-1 border border-gray-300 hover:bg-gray-50 text-gray-700 py-3 rounded-xl font-semibold transition text-center flex items-center justify-center gap-2">
                <i class="fa-solid fa-times"></i>
                Cancel
            </a>
        </div>
    </form>
</div>

<script>
    // Optional: Add validation for student ID format
    document.querySelector('form').addEventListener('submit', function(e) {
        const studentId = document.querySelector('input[name="student_id"]').value;
        if (studentId && !/^[a-zA-Z0-9\-]+$/.test(studentId)) {
            e.preventDefault();
            alert('Student ID can only contain letters, numbers, and hyphens.');
        }
    });
</script>
@endsection