<?php

namespace App\Http\Controllers\Auth;

use DB;
use Mail;
use Session;
use App\User;
use Illuminate\Validation\Rule;
use Illuminate\Http\Request;
use App\Mail\EmailVerification;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;

use Illuminate\Foundation\Auth\RegistersUsers;


use App\Physician;

class RegisterController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Register Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles the registration of new users as well as their
    | validation and creation. By default this controller uses a trait to
    | provide this functionality without requiring any additional code.
    |
    */

    use RegistersUsers;

    /**
     * Where to redirect users after registration.
     *
     * @var string
     */
    protected $redirectTo = '/home';

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest');
    }

    /**
     * Get a validator for an incoming registration request.
     *
     * @param  array  $data
     * @return \Illuminate\Contracts\Validation\Validator
     */
    protected function validator(array $data)
    {
        $data['email'] = strtolower($data['email']);

        $rules = [
            'email' =>  'required|string|email|max:255|unique:users,email|exists:physicians|email',
            'password' => 'required|string|min:6|confirmed',
        ];

        $messages = [
            'email.exists' => 'Your e-mail address is not associated with a known Physician'
        ];
        return Validator::make($data, $rules, $messages);
    }

    /**
     * Create a new user instance after a valid registration.
     *
     * @param  array  $data
     * @return \App\User
     */
    protected function create(array $data)
    {
        return User::create([
            'email' => $data['email'],
            'password' => bcrypt($data['password']),
            'email_token' => str_random(64)
        ]);
    }

    public function register(Request $request)
    {
        // Laravel validation
        $validator = $this->validator($request->all())->validate();
        // if ($validator->fails())
        // {
        //     $this->throwValidationException($request, $validator);
        // }


        DB::beginTransaction();
        try
        {
            $user = $this->create($request->all());
            // After creating the user send an email with the random token generated in the create method above
            $email = new EmailVerification(new User(['email_token' => $user->email_token, 'email' => $user->email]));
            Mail::to($user->email)->send($email);
            DB::commit();
            Session::flash('message', 'We have sent you a verification email!');
            return back();
        }
        catch(Exception $e)
        {
            DB::rollback();
            return back();
        }
    }
    public function verify($token)
    {
        // The verified method has been added to the user model and chained here
        // for better readability
        User::where('email_token',$token)->firstOrFail()->verified();
        return redirect('login');
    }
}
