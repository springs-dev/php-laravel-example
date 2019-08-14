<?php

namespace App\Console\Commands;

use App\Models\Training;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class DayTrainings extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'DayTrainings:check';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send emails when the training is in one day from today.';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {

        $now = Carbon::now()->format('Y-m-d');
        $now_plus_day = Carbon::now()->addDay()->format('Y-m-d');
        $tr = Training::with('responsive_users', 'responsive_users.user', 'training_course')
            ->where('date_start', '=', $now_plus_day)
            ->where('date_end', '>=', $now)
            ->where('type', Training::TYPE_PLANNED)
            ->where(function($q) {
                $q->where('status', Training::STATUS_PLANNED)->orWhere('status', Training::STATUS_IN_PLANNING);
            })
            ->get();

        if (count($tr) > 0) {
            foreach ($tr as $val) {
                $emails = [];
                $data = [
                    'url_executed' => env('TEMPORARY_SITE').'/changestatustraining/'.$val->id,
                    'url_delete' => env('TEMPORARY_SITE').'/changestatustraining/'.$val->id,
                    'url_change' => env('TEMPORARY_SITE').'/changestatustraining/'.$val->id,
                    'names' => '',
                    'training' => $val->training_course->name,
                    'logo' => url('images/logo-spec.png'),
                    'logo_fb' => url('images/social/fb-spec.png'),
                    'logo_inst' => url('images/social/inst-spec.png'),
                    'logo_tw' => url('images/social/tw-spec.png'),
                    'logo_youtube' => url('images/social/youtube-spec.png'),
                    'logo_xing' => url('images/social/xing-spec.png'),
                    'logo_linkedin' => url('images/social/link-spec.png'),
                    'logo_kununu' => url('images/social/kununu-spec.png'),
                ];

                if (count($val->responsive_users) > 0) {
                    $cnt = count($val->responsive_users);
                    $i = 0;

                    foreach ($val->responsive_users as $user) {
                        if ($cnt == ($i+1)) {
                            $data['names'] .= $user->name;
                        } else {
                            $data['names'] .= $user->name.', ';
                        }

                        $emails[] = $user->email;
                        $i++;
                    }
                }
                try {
                    Mail::to($emails)->send( new \App\Mail\DayTrainings($data) );
                } catch (\Exception $e) {
                    continue;
                }


                $val->is_day = 1;
                $val->save();


            }

        }

    }
}
