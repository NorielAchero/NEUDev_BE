<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Classroom;
use App\Models\Teacher;
use App\Models\Student;

class ClassroomSeeder extends Seeder
{
    public function run()
    {
        $teacher = Teacher::first();
        $students = Student::take(5)->get(); // Get first 5 students

        $class = Classroom::create([
            'className' => 'Introduction to Programming',
            'teacherID' => $teacher->teacherID,
        ]);

        $class->students()->attach($students);
    }
}
