<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\BaseController;
use App\Http\Requests\SignUpRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;

class AuthController extends BaseController
{
    public function setModel()
    {
        $this->model = new User();
    }

    public function login(Request $request)
    {
        $credentials = $request->all();
        $user = $this->attempt($credentials);

        $auth = $this->accessToken($credentials);

        $auth['user'] = $user;

        return response()->json($auth);
    }

    public function attempt($credentials)
    {
        $email = $credentials['email'];
        $password = $credentials['password'];
        $user = $this->loginUserByEmail($email);

        if (!$user || !Hash::check($password, $user->getAuthPassword())) {
            return null;
        }

        return $user;
    }

    public function accessToken($credentials)
    {
        $username = $credentials['email'];
        $password = $credentials['password'];
        $oauthClient = $this->getClient();
        $data = [
            'grant_type' => 'password',
            'client_id' => $oauthClient->id,
            'client_secret' => $oauthClient->secret,
            'username' => $username,
            'password' => $password,
        ];
        $url = config('app.url') . '/oauth/token';
        request()->request->add($data);
        $request = \Illuminate\Support\Facades\Request::create($url, 'POST');
        $content = Route::dispatch($request)->getContent();

        return json_decode($content, true);
    }

    public function loginUserByEmail($email)
    {
        return $this->query->where('email', $email)->first();
    }

    public function signUp(SignUpRequest $request)
    {
        $data = $request->all();

        $user = $this->query->create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => bcrypt($data['password'])
        ]);

        return response()->json([
            'status' => 'Đăng ký thành công',
            'data' => $user,
        ], 200);
    }

    public function getClient()
    {
        return DB::table('oauth_clients')
            ->where('provider', 'users')
            ->first();
    }
}
