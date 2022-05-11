<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Illuminate\Support\Facades\Auth;
use JWTAuth;

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
        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => $this->guard()->factory()->getTTL() * 60
        ]);
    }

    /**
     * Handle an authentication attempt.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function login(Request $request){
        $credentials = $request->validate([
            'email' => 'required|email', //|exists:users,email
            'password' => ['required'],
        ]);

        $user = User::where('email', $request->email)->first();
        if(!$user) 
            return response()->json(['message' => "User with email {$request->email} does not exist"], 404);

        try { 
            if(!$token = $this->guard()->login($user)) {
                return response()->json(['error' => 'invalid_credentials', 'token' => $token], 401);
            } 

            return $this->respondWithToken($token);
            
            // $user = $this->guard()->user();
            // return $user->user
            //return response()->json(['error' => 'Unauthorized'], 401);

        } catch (\Exception $e) {
            return response()->json(['error' => 'could_not_create_token', 'message'=>$e->getMessage()], 500); 
        }

        return response()->json(['error' => 'Unauthorized'], 401);
    }

    /**
     * 
     * Verify a User
     * 
     * Get a JWT token via given credentials.
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function getAuthenticatedUser()
    {
        $user = $this->guard()->user();

        if(empty($user)){
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        return response()->json(['user' => $user], 200);
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
        return response()->json(['error' => 'Unauthorized'], 401);
    }
    
    /**
     * Logout a user.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout()
    {
        $this->guard()->logout();

        return response()->json(['message' => 'User successfully signed out']);
    }
}
