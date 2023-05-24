<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Profile;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Laravel\Sanctum\PersonalAccessToken;
use Symfony\Component\HttpFoundation\Response;

class AuthenticationController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
        $user = User::all();
        return response()->json(['message' => 'success', 'data' => $user]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function register(Request $request)
    {
        //
        // dd($request->all());
        $validator = Validator::make($request->all(), [
            'username' => 'required|unique:users,name,except,id',
            'email' => 'required|email|unique:users,email,except,id',
            'password' => 'required|min:5',
            'date_of_birth' => 'date',
            // 'gender' => 'required',
            // 'full_name' => 'required',
        ]);
        // $request->profile_picture = "default";
        if ($validator->fails()) {
            return response()->json([
                'result' => false,
                'message' => $validator->getMessageBag(),
                'data' => null,
                'error' => null
            ], Response::HTTP_BAD_REQUEST);
        }
        try {
            //code...
            DB::beginTransaction();
            $user = User::create([
                'name' => $request->full_name,
                'email' => $request->email,
                'password' => Hash::make($request->password)
            ]);
            $member = Profile::create([
                'user_id' => $user->id,
                'full_name' => $request->full_name,
                'date_of_birth' => $request->date_of_birth,
                'gender' => $request->gender,
                'profil_picture' => $request->profile_picture,
                'role' => 'STUDENT'
            ]);
            DB::commit();
            return response()->json([
                'result' => true,
                'message' => 'Success created new user and member',
                'data' => [
                    'user' => $user,
                    'member' => $member,
                ],
                'error' => null
            ], Response::HTTP_OK);
        } catch (\Throwable $th) {
            //throw $th;
            return response()->json([
                'result' => false,
                'message' => 'Failed created new user and member',
                'data' => [
                    'user' => null,
                    'member' => null,
                ],
                'error' => $th->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request)
    {
        //
        // $token = Hash::make($request->header('Authorization'));
        // dd($token);
        // $userToken = PersonalAccessToken::findToken($token);
        // dd($userToken);
        // $user = $userToken->tokenable;
        // return $user;
        return User::get();
        // return auth('sanctum')->user()->profile;
    }

    /**
     * Update the specified resource in storage.
     */
    public function login(Request $request)
    {
        //

        $validator = Validator::make($request->all(), [
            'email_or_username' => 'required',
            'password' => 'required',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'result' => false,
                'message' => $validator->getMessageBag(),
                'data' => null,
                'error' => ""
            ], Response::HTTP_BAD_REQUEST);
        }

        $user = User::where('email', $request->email_or_username)
            ->orWhere('name', $request->email_or_username)->first();
        $profile = Profile::where('user_id', $user->id)
            ->first();
        if (!Hash::check($request->password,  $user->password)) return response()->json([
            'result' => false,
            'message' => 'Username, Email or Password Incorrect',
            'data' => null,
            'error' => ""
        ], Response::HTTP_BAD_REQUEST);
        // $authUser = Auth::user();
        $token = $user->createToken('LearningApp')->plainTextToken;
        return response()->json([
            'result' => true,
            'message' => 'Login Success',
            'token' => $token,
            'data' => [
                'id_user' => $user->id,
                'email' => $user->email,
                'name' => $profile->full_name,
                'role' => $profile->role,
            ],
            'error' => ""
        ], Response::HTTP_OK);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
