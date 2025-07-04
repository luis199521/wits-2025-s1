<?php
/**
 * Course Management API Controller.
 * - Allows unauthenticated users to Browse & Read Courses.
 * - Provides course management functions (BREAD) for administrative users.
 *
 * Filename:        CourseController.php
 * Location:        app/Http/Controllers/Api/v1/
 * Project:         wits-2025-s1
 * Date Created:    22/04/2025
 *
 * Author:          Corin Little <20135656@tafe.wa.edu.au>
 * Student ID:      20135656
 *
 * Assessment:      WITS-2025-S1
 * Cluster:         SaaS: Part 2 – Back End Development
 * Qualification:   ICT50220 Diploma of Information Technology (Back End Web Development)
 * Year/Semester:   2025/S1
 *
 */

namespace App\Http\Controllers\Api\v1;

use App\Classes\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\v1\DeleteCourseRequest;
use App\Http\Requests\v1\StoreCourseRequest;
use App\Http\Requests\v1\UpdateCourseRequest;
use App\Models\Course;
use App\Models\Package;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

require_once base_path("app/helpers/helpers.php");

class CourseController extends Controller
{
    /**
     * Browse: Displays a list of all courses with search functionality.
     * @param  Request  $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
        ]);

        $search = $validated['search'] ?? null;

        $msg = 'Found all '. Course::count() .' courses';

        if ($search) {
            $courses = Course::select('courses.*')
                ->whereAny(['national_code','aqf_level','title','tga_status','state_code','nominal_hours',], 'like', "%$search%")
                ->get();

            $msg = "Search results for: '$search' [". $courses->count() ." of ". Course::count() ." course(s) found]";
        } else {
            $courses = Course::all();
        }

        if ($courses->count() > 0) {
            return ApiResponse::success($courses, $msg);
        }
        return ApiResponse::error([], 'No courses found', 404);
    }

    /**
     * Create a new course & store it in the database.
     * @param  StoreCourseRequest  $request
     * @return JsonResponse
     */
    public function store(StoreCourseRequest $request): JsonResponse
    {
        if (Auth::user()->cannot('course add')) {
            return ApiResponse::error([], "You are not authorised to add new courses.", 403);
        }

        $validated =  cleanCourseRequest($request->all());

        $course = Course::create($validated);

        $course->clusters()->attach($validated['cluster_id']);
        $course->units()->attach($validated['unit_id']);

        if (!empty($course)) {
            return ApiResponse::success(Course::find($course->id), "Course added", 201);
        }
        return ApiResponse::error($course, "Course creation failed", 404);
    }

    /**
     * Display a course's details.
     * @param  string  $id
     * @return JsonResponse
     */
    public function show(string $id): JsonResponse
    {
        $course = Course::find($id);

        if (!$course) {
            return ApiResponse::error([], "Course not found", 404);
        }

        return ApiResponse::success($course, "Course found");
    }

    /**
     * Update the specified course in the database.
     * @param  UpdateCourseRequest  $request
     * @param  string  $id
     * @return JsonResponse
     */
    public function update(UpdateCourseRequest $request, string $id): JsonResponse
    {
        if (Auth::user()->cannot('course edit')) {
            return ApiResponse::error([], "You are not authorised to update this course.", 403);
        }

        $course = Course::find($id);

        if (!$course) {
            return ApiResponse::error([], "Course not found", 404);
        }

        $validated =  cleanCourseRequest($request->validated());
        $updateBool = $course->update($validated);

        $course = Course::find($id);
        $course->clusters()->sync($validated['cluster_id']);
        $course->units()->sync($validated['unit_id']);

        if ($updateBool) {
            return ApiResponse::success($course, "Course updated", 201);
        }
        return ApiResponse::error($course, "Course update failed", 404);
    }

    /**
     * Remove the specified course from the database.
     * @param  DeleteCourseRequest  $request
     * @param  string  $id
     * @return JsonResponse
     */
    public function destroy(DeleteCourseRequest $request, string $id): JsonResponse
    {
        if (Auth::user()->cannot('course delete')) {
            return ApiResponse::error([], "You are not authorised to delete this course.", 403);
        }

        $course = Course::find($id);

        if (!$course) {
            return ApiResponse::error([], "Course not found", 404);
        }

        $course->clusters()->detach();
        $course->units()->detach();
        $course->delete();

        return ApiResponse::success($course, "Course '$course->aqf_level $course->title' deleted");
    }
}
