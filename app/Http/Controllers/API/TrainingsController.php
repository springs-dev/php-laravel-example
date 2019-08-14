<?php

namespace App\Http\Controllers\API;

use App\Models\Employee;
use App\Models\Training;
use App\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Validator;
use Illuminate\Validation\Rule;

class TrainingsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
    //* @return \Illuminate\Http\Response
     */
    public function index()
    {
        $allTrainings = Training::with('participants', 'participants.customer', 'participants.company', 'participants.position', 'participants_fill_in', 'participants_fill_in.customer', 'participants_fill_in.company', 'participants_fill_in.position', 'training_course', 'training_group', 'responsive_users')->where('type', Training::TYPE_PLANNED)->get();

        if ($allTrainings) {
            return response()->json($allTrainings, 200);
        } else {
            return response()->json([
                'message' => 'Expectation Failed',
                'status' => 417
            ], 417);
        }

    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \App\Http\Requests\UserGroupsRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'status' => 'required|integer',
            'lecturer' => 'nullable|string',
            'date_start' => 'required|date_format:"Y-m-d"',
            'date_end' => 'required|date_format:"Y-m-d"',
            'location' => 'required|string',
            'days' => 'required|integer',
            'cost' => 'required|numeric',
            'provider_company' => 'required|string',
            'training_group_id' => 'required|integer|exists:training_groups,id,id,'.$request->training_group_id,
            'training_course_id' => 'required|integer|exists:training_courses,id,id,'.$request->training_course_id,
            'participants' => 'nullable|array',
            'participants_fill_in' => 'nullable|array',
            'responsive_users' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors(),
                'status' => 417
            ], 417);
        }

        $participants_fill_in = 0;
        $participants = 0;

        if ($request->input('participants_fill_in')) {
            $participants_fill_in = count($request->input('participants_fill_in'));
        }

        if ($request->input('participants')) {
            $participants = count($request->input('participants'));
        }

        $result = new Training;

        $result->type = Training::TYPE_PLANNED;
        $result->status = $request->input('status');
        $result->lecturer = $request->input('lecturer');
        $result->date_start = $request->input('date_start');
        $result->date_end = $request->input('date_end');
        $result->location = $request->input('location');
        $result->days = $request->input('days');
        $result->cost_per_day = $request->input('cost') / $request->input('days');
        $result->cost = $request->input('cost');
        $result->provider_company = $request->input('provider_company');
        $result->training_group_id = $request->input('training_group_id');
        $result->training_course_id = $request->input('training_course_id');
        $result->cost_per_participant = $request->input('cost') / ( $participants_fill_in + $participants );

        // check exist is users in participants_fill_in, participants, responsive_users

        if ( !empty($request->input('participants_fill_in')) ) {
            foreach ($request->input('participants_fill_in') as $participants_fill_in) {

                try {
                    Employee::findOrFail($participants_fill_in);
                } catch (\Exception $e) {
                    return response()->json([
                        'message' => 'Some of the Employees has been deleted. (from participants fill in)',
                        'status' => 417
                    ], 417);
                }

            }
        }

        if ( !empty($request->input('participants')) ) {
            foreach ($request->input('participants') as $participants) {

                try {
                    Employee::findOrFail($participants);
                } catch (\Exception $e) {
                    return response()->json([
                        'message' => 'Some of the Employees has been deleted. (from participants)',
                        'status' => 417
                    ], 417);
                }

            }
        }

        if ( !empty($request->input('responsive_users')) ) {
            foreach ($request->input('responsive_users') as $responsive_users) {

                try {
                    $employee = Employee::where('id', $responsive_users)->with('user', 'user.userGroup')->first();
                } catch (\Exception $e) {
                    return response()->json([
                        'message' => 'One of the responsible users has been deleted, please add new.',
                        'status' => 417
                    ], 417);
                }

                if (!isset($employee) || $employee->user->userGroup->name != 'ACADEMY') {
                    return response()->json([
                        'employee' => $employee,
//                        'message' => 'Some of the Responsive Users not part of a group ACADEMY.',
                        'message' => 'One of the responsible users has been deleted, please add new.',
                        'status' => 417
                    ], 417);
                }
            }
        }


        $result->save();
//        $result->participants_fill_in()->sync($request->input('participants_fill_in'));
//        $result->participants()->sync($request->input('participants'));
//        $result->responsive_users()->sync($request->input('responsive_users'));

        try {
            if ( !empty($request->input('participants_fill_in')) ) {
                $result->participants_fill_in()->sync($request->input('participants_fill_in'));
            }
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Some of the Employees has been deleted.',
                'status' => 417
            ], 417);
        }


        try {
            if ( !empty($request->input('participants')) ) {
                $result->participants()->sync($request->input('participants'));
            }
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Some of the Employees has been deleted.',
                'status' => 417
            ], 417);
        }

        try {
            if ( !empty($request->input('responsive_users')) ) {
                $result->responsive_users()->sync($request->input('responsive_users'));
            }
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Some of the Responsive Users has been deleted.',
                'status' => 417
            ], 417);
        }


        if ($result) {
            return response()->json([
                'message' => 'Added',
                'id' => $result->id,
                'status' => 200
            ], 200);
        } else {
            return response()->json([
                'message' => 'Expectation Failed',
                'status' => 417
            ], 417);
        }

    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $training = Training::with('participants', 'participants.customer', 'participants.company', 'participants.position', 'participants_fill_in', 'participants_fill_in.customer', 'participants_fill_in.company', 'participants_fill_in.position', 'training_course', 'training_group', 'responsive_users')->where('id', $id)->first();

        if ($training) {
            return response()->json($training, 200);
        } else {
            return response()->json([
                'message' => 'Expectation Failed',
                'status' => 417
            ], 417);
        }
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {

        $validator = Validator::make($request->all(), [
            'status' => 'required|integer',
            'lecturer' => 'nullable|string',
            'date_start' => 'required|date_format:"Y-m-d"',
            'date_end' => 'required|date_format:"Y-m-d"',
            'location' => 'required|string',
            'days' => 'required|integer',
            'cost' => 'required|numeric',
            'provider_company' => 'required|string',
            'training_group_id' => 'required|integer|exists:training_groups,id,id,'.$request->training_group_id,
            'training_course_id' => 'required|integer|exists:training_courses,id,id,'.$request->training_course_id,
            'participants' => 'required|array',
            'responsive_users' => 'nullable|array'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors(),
                'status' => 417
            ], 417);
        }

        $participants_fill_in = 0;
        $participants = 0;

        if ($request->input('participants_fill_in')) {
            $participants_fill_in = count($request->input('participants_fill_in'));
        }

        if ($request->input('participants')) {
            $participants = count($request->input('participants'));
        }

        $training = Training::findOrFail($id);

        if ($training) {
            $training->type = Training::TYPE_PLANNED;
            $training->status = $request->input('status');
            $training->lecturer = $request->input('lecturer');
            $training->date_start = $request->input('date_start');
            $training->date_end = $request->input('date_end');
            $training->location = $request->input('location');
            $training->days = $request->input('days');
            $training->cost_per_day = $request->input('cost') / $request->input('days');
            $training->cost = $request->input('cost');
            $training->provider_company = $request->input('provider_company');
            $training->training_group_id = $request->input('training_group_id');
            $training->training_course_id = $request->input('training_course_id');
            $training->cost_per_participant = $request->input('cost') / ( $participants + $participants_fill_in );

            try {
                if ( !empty($request->input('participants_fill_in')) ) {
                    $training->participants_fill_in()->sync($request->input('participants_fill_in'));
                }
            } catch (\Exception $e) {
                return response()->json([
                    'message' => 'Some of the Employees has been deleted.',
                    'status' => 417
                ], 417);
            }

            try {
                if ( !empty($request->input('participants')) ) {
                    $training->participants()->sync($request->input('participants'));
                }
            } catch (\Exception $e) {
                return response()->json([
                    'message' => 'Some of the Employees has been deleted.',
                    'status' => 417
                ], 417);
            }

            try {
                if ( !empty($request->input('responsive_users')) ) {
                    $training->responsive_users()->sync($request->input('responsive_users'));
                }
            } catch (\Exception $e) {
                return response()->json([
                    'message' => 'Some of the Responsive Users has been deleted.',
                    'status' => 417
                ], 417);
            }

            $training->save();

            return response()->json([
                'message' => 'Updated',
                'status' => 200
            ], 200);
        } else {
            return response()->json([
                'message' => 'Expectation Failed',
                'status' => 417
            ], 417);
        }

    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {

        if (Training::destroy($id)) {
            return response()->json([
                'message' => 'Deleted',
                'status' => 200
            ], 200);
        } else {
            return response()->json([
                'message' => 'Expectation Failed',
                'status' => 417
            ], 417);
        }
    }

    public function changeDate (Request $request, $id) {

        $validator = Validator::make($request->all(), [
            'date_start' => 'required|date_format:"Y-m-d"',
            'date_end' => 'required|date_format:"Y-m-d"'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors(),
                'status' => 417
            ], 417);
        }

        $training = Training::where('id', $id)->first();

        if ($training) {

            $training->date_start = $request->input('date_start');
            $training->date_end = $request->input('date_end');
            $training->is_change_date = 1;
            $training->status = Training::STATUS_POSTPONED;
            $training->save();

            return response()->json([
                'message' => 'Updated',
                'status' => 200
            ], 200);
        } else {
            return response()->json([
                'message' => 'Training is not exist',
                'status' => 417
            ], 417);
        }

    }

    public function moveToExecute ($id) {
        $training = Training::where('id', $id)->first();

        if ($training) {

            $now = Carbon::now();


            if ( $now->diffInDays(Carbon::parse($training->date_end), false) < 0 ) {
                $training->status = Training::STATUS_IMPLEMENTED;
            } elseif( $now->diffInDays(Carbon::parse($training->date_start), false) >= 0 &&  $now->diffInDays(Carbon::parse($training->date_end), false) <= 0 ) {
                $training->status = Training::STATUS_IN_PROGRESS;
            } elseif ($now->diffInDays(Carbon::parse($training->date_end), false) > 0) {
                $training->status = Training::STATUS_IN_PROGRESS;
            }

            $training->type = Training::TYPE_EXECUTED;
            $training->save();

            return response()->json([
                'message' => 'Moved to execute trainings',
                'status' => 200
            ], 200);
        } else {
            return response()->json([
                'message' => 'Training is not exist',
                'status' => 417
            ], 417);
        }
    }

    public function nonActive ($id) {
        $training = Training::where('id', $id)->first();

        if ($training) {

            $training->status = Training::STATUS_INACTIVE;
            $training->save();

            return response()->json([
                'message' => 'Trainings was moved to inactive',
                'status' => 200
            ], 200);
        } else {
            return response()->json([
                'message' => 'Training is not exist',
                'status' => 417
            ], 417);
        }
    }

    public function responsiveUsers () {

        $users = User::whereHas('userGroup', function($query) {
            $query->where('name', 'ACADEMY');
        })->with('employee')->get();

        if ($users) {
            return response()->json([
                'message' => $users,
                'status' => 200
            ], 200);
        } else {
            return response()->json([
                'message' => 'Users are not exist',
                'status' => 417
            ], 417);
        }

    }

}
