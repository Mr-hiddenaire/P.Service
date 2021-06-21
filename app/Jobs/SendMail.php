<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

use Illuminate\Support\Facades\Mail;

class SendMail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $mailType;
    
    protected $mailData;
    
    protected $sendToAdress;
    
    protected $sendToName;
    
    protected $sendTitle;
    
    protected $sendBody;
    
    protected $mailTemplate;
    
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(int $mailType, string $sendTitle, array $mailData)
    {
        $this->sendToAdress = config('mail.to.address');
        
        $this->sendToName = config('mail.to.name');
        
        $this->setMailType($mailType);
        
        $this->setSendTitle($sendTitle);
        
        $this->setMailData($mailData);
    }

    private function setMailType(int $mailType)
    {
        $this->mailType = $mailType;
    }
    
    private function setSendTitle(string $sendTitle)
    {
        $this->sendTitle = $sendTitle;
    }
    
    private function setMailData(array $mailData)
    {
        $this->mailData = $mailData;
    }
    
    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        switch ($this->mailType) {
            case 1:
                $this->doTorrentNoticeMail();
                break;
            case 2:
                $this->doHlsUploadDoneMail();
                break;
        }
    }
    
    private function doTorrentNoticeMail()
    {
        $sendToAddress = $this->sendToAdress;
        $sendToName = $this->sendToName;
        $sendTitle = $this->sendTitle;
        $data = $this->mailData;
        
        Mail::send('emails.torrent_notice_to_download', $this->mailData, function($message) use ($sendToAddress, $sendToName, $sendTitle, $data) {
            $message->to($sendToAddress, $sendToName)
            ->subject($sendTitle);
        });
    }
    
    private function doHlsUploadDoneMail()
    {
        $sendToAddress = $this->sendToAdress;
        $sendToName = $this->sendToName;
        $sendTitle = $this->sendTitle;
        $data = $this->mailData;
        
        Mail::send('emails.upload_successfully', $this->mailData, function($message) use ($sendToAddress, $sendToName, $sendTitle, $data) {
            $message->to($sendToAddress, $sendToName)
            ->subject($sendTitle)
            ->attach($data['thumbnail'])
            ->attach($data['preview']);
        });
    }
}
