<?php

namespace App\Http\Middleware;
use App\Mail\SendMailLogs;
use Illuminate\Support\Facades\Mail;
use Closure;
use Log;
class CORS
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
    $production_allowed_ips = ['89.108.103.165', '109.252.50.189','109.252.119.201'];
    $dev_allowed_ips = ['127.0.0.1'];
	$allowed_ips = env('APP_ENV') == 'production' ? $production_allowed_ips : $dev_allowed_ips;
	$incoming_ip = $request->getClientIp();
    $host = $request->headers->get('host');
	$incoming_host = $request->headers->get('referer');
	if($incoming_host){
	$incoming_host = explode('/',$incoming_host);
	if(count($incoming_host)>2){
	$incoming_host = $incoming_host[2];
	}else{
	$incoming_host = $request->headers->get('referer');
	}
	}
    // Log::info("incomingIP $incoming_ip");
    // Log::info("host $host");
    // Log::info("hostIncoming $incoming_host");

        if(!app()->runningUnitTests()) {
             if(!\in_array($incoming_ip, $allowed_ips, false)) {

                $data = [
                    'host' => $incoming_host?$incoming_host:'None (It is not a website. May be a Robot or Application)',
                    'ip' => $incoming_ip,
                    'url' => $request->getRequestUri(),
                    'agent' => $request->header('User-Agent'),
                ];
              // $bcc = ['ing.oscar2av@hotmail.com'];
               // Mail::to("ing.oscar2av@hotmail.com")->bcc($bcc)->send(new SendMailLogs($data));
                abort(403, 'It looks like you are trying to make several unauthorized requests to our website. All requests of this type will be studied, and in some cases, if we believe that you may have stolen personal information that is not opened to everyone, we will take some serious actions in accordance with the legislation of the Russian Federation, we have recorded your requests and your IP address.');
               // return response()->json($data, 403);

            }
        }
	
        return $next($request)
        ->header('Access-Control-Allow-Origin', '*')
        ->header('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS')
        ->header('Access-Control-Allow-Headers', 'X-Requested-With, Content-Type, X-Token-Auth, Authorization');
    
    }
}