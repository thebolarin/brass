<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Illuminate\Support\Facades\Auth;
use JWTAuth;
use Illuminate\Support\Facades\Validator;

class LoginController extends Controller
{
    /**
     * Get the guard to be used during authentication.
     *
     * @return \Illuminate\Contracts\Auth\Guard
     */
    public function guard(){
        return Auth::guard('user');
    }

    /**
     * Get the token array structure.
     *
     * @param  string $token
     *
     * @return \Illuminate\Http\JsonResponse
     */
    protected function respondWithToken($token){
        $data = [
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => $this->guard()->factory()->getTTL() * 60
        ];

        return $this->respond($data);
    }

    /**
     * Handle an authentication attempt.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function login(Request $request){
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) return $this->respond($validator->errors(), 400, "Error");

        $user = User::where('email', $request->email)->first();
        if(!$user) return $this->respond("User with email {$request->email} does not exist", 400, "Error");

        try { 
            if(!$token = $this->guard()->login($user)) return $this->respond("Invalid_credentials", 401, "Error");
            return $this->respondWithToken($token);

        } catch (\Exception $e) {
            // return response()->json(['error' => 'could_not_create_token', 'message'=>$e->getMessage()], 500); 
            return $this->respond($e);
        }

        return $this->respond("Unauthorized", 401, "Error");
    }

    /**
     * 
     * Verify a User
     * 
     * Get a JWT token via given credentials.
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function getAuthenticatedUser(Request $request)
    {
        return $this->respond($request->user);
    }

    /**
     * Refresh User token.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function refresh()
    {
        if($this->guard()->user()){
            return $this->respondWithToken($this->guard()->refresh());
        }

        return $this->respond("Unauthorized", 401, "Error");
    }
    
    /**
     * Logout a user.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout()
    {
        $this->guard()->logout();

        return $this->respond("User successfully signed out");
    }
}
