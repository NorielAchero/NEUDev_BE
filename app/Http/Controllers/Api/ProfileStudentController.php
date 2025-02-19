<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Student;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log; // Import Log Facade

class ProfileStudentController extends Controller
{
    // 📌 GET Student Profile
    public function show(Student $student)
    {
        Log::info("Student Profile Accessed", ['studentID' => $student->studentID]);
    
        // Convert relative paths to full URLs using asset('storage/...')
        $student->profileImage = $student->profileImage ? asset('storage/' . $student->profileImage) : null;
        $student->coverImage   = $student->coverImage ? asset('storage/' . $student->coverImage) : null;
    
        return response()->json($student, 200);
    }

    // 📌 UPDATE Student Profile
    public function update(Request $request, $studentID)
    {
        Log::info("Update request received", ['studentID' => $studentID, 'requestData' => $request->all()]);
    
        // Find the student using studentID
        $student = Student::where('studentID', $studentID)->first();
        if (!$student) {
            Log::warning("Student not found", ['studentID' => $studentID]);
            return response()->json(['error' => 'Student not found'], 404);
        }
    
        // Validate input
        $validated = $request->validate([
            'firstname'    => 'sometimes|string|max:255',
            'lastname'     => 'sometimes|string|max:255',
            'email'        => 'sometimes|email|unique:students,email,' . $student->studentID . ',studentID',
            'student_num'  => 'sometimes|string|unique:students,student_num,' . $student->studentID . ',studentID',
            'program'      => 'sometimes|in:BSCS,BSIT,BSEMC,BSIS',
            'password'     => 'sometimes|string|min:8',
            'profileImage' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:10048',
            'coverImage'   => 'nullable|image|mimes:jpeg,png,jpg,gif|max:10048',
        ]);
    
        // Handle profile image upload if a new file is provided
        if ($request->hasFile('profileImage')) {
            if ($student->profileImage) {
                Storage::delete($student->profileImage);
            }
            $validated['profileImage'] = $request->file('profileImage')->store('profile_images', 'public');
        }
        // Otherwise, ensure we don't accidentally override the current value
        else {
            // If the request contains an empty value, remove it.
            if (isset($validated['profileImage']) && $validated['profileImage'] === "") {
                unset($validated['profileImage']);
            }
        }
    
        // Handle cover image upload if a new file is provided
        if ($request->hasFile('coverImage')) {
            if ($student->coverImage) {
                Storage::delete($student->coverImage);
            }
            $validated['coverImage'] = $request->file('coverImage')->store('cover_images', 'public');
        }
        else {
            if (isset($validated['coverImage']) && $validated['coverImage'] === "") {
                unset($validated['coverImage']);
            }
        }
    
        // Hash password if provided
        if ($request->filled('password')) {
            $validated['password'] = bcrypt($validated['password']);
        }
    
        // Remove any keys with empty values (optional)
        $validated = array_filter($validated, function ($value) {
            return $value !== "";
        });
    
        Log::info("Updating student with data", ['studentID' => $student->studentID, 'updateData' => $validated]);
    
        // Update the student with the validated (and filtered) data
        $student->update($validated);
    
        Log::info("Student profile updated successfully", ['studentID' => $student->studentID]);
    
        return response()->json(['message' => 'Profile updated successfully', 'student' => $student], 200);
    }    
    
    // 📌 DELETE Student Profile
    public function destroy(Student $student)
    {
        // Delete stored images if they exist
        if ($student->profileImage) {
            Storage::delete($student->profileImage);
        }
        if ($student->coverImage) {
            Storage::delete($student->coverImage);
        }

        $student->delete();
        Log::warning("Student Profile Deleted", ['studentID' => $student->studentID]);

        return response()->json(['message' => 'Profile deleted successfully'], 200);
    }
}