<?php

namespace Rogue\Listeners;

use Rogue\Events\PostTagged;
use Illuminate\Support\Facades\Notification;
use Rogue\Notifications\SlackTagNotification;

class SendTaggedNotification
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  PostTagged  $event
     * @return void
     */
    public function handle(PostTagged $event)
    {
        $post = $event->post;
        $tag = $event->tag;
        $adminId = $event->adminId;

        info('post_tagged', ['id' => $post->id, 'tag' => $tag->tag_slug]);

        if (! ($post->hasGoodTag()) && in_array($tag->tag_slug, $post->goodTags)) {
            Notification::route('slack', config('services.slack.url'))
                ->notify(new SlackTagNotification($post, $tag, $adminId));
        }
    }
}
