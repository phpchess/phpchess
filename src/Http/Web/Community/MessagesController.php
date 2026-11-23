<?php

declare(strict_types=1);

namespace PhpChess\Http\Web\Community;

use PhpChess\Domain\ActionRefused;
use PhpChess\Http\Request;
use PhpChess\Http\Response;
use PhpChess\Http\Web\MemberPages;
use PhpChess\Kernel\Services;

/**
 * Direct messages (C-04), all behind the switch "direct messages" (K8).
 *
 * Addresses (Routes.php), members only:
 *   GET  /messages                  my conversations, newest first
 *   GET  /messages/{name}           one conversation, and a form to write
 *   GET  /messages/{name}/new?after=ID  the newer messages as JSON (the page polls it, A41)
 *   POST /messages/{name}           send a message
 * The rules (class limit, length, word filter, sending limit) are in Messages.
 */
final class MessagesController
{
    public function __construct(
        private readonly Services $services,
        private readonly MemberPages $pages,
    ) {
    }

    public function inbox(Request $request): Response
    {
        $user = $this->pages->user();
        // The "Write to" form sends ?to=NAME: go to that conversation.
        $to = MemberPages::nameFrom(['name' => trim($request->queryValue('to'))]);
        if ($to !== '') {
            return Response::redirect($request->basePath . '/messages/' . rawurlencode($to));
        }

        return $this->pages->render($request, 'community/inbox', 'messages.title', [
            'conversations' => $this->services->messageRepository()->conversationsOf($user->id),
        ]);
    }

    /**
     * @param array<string, string> $parts
     */
    public function conversation(Request $request, array $parts): Response
    {
        $user = $this->pages->user();
        $other = $this->services->users()->findByUsername(MemberPages::nameFrom($parts));
        if ($other === null || $other->id === $user->id) {
            return $this->pages->notFound($request);
        }
        $messages = $this->services->messages()->open($user->id, $other->id, 0, $this->services->now);
        $this->services->play()->noticeRepository()->markKindRead($user->id, 'message_received', $this->services->now);

        return $this->services->pages()->render($request, 'community/conversation', $this->pages->text('messages.with', ['name' => $other->username]), [
            'other' => $other,
            'messages' => $messages,
            'mayWrite' => $other->isActive() && $this->services->play()->classRules()->mayContact($user->id, $other->id),
            'lastId' => $messages === [] ? 0 : $messages[count($messages) - 1]['id'],
        ]);
    }

    /**
     * The messages after ?after=ID as JSON, for the page's polling while it is open.
     *
     * @param array<string, string> $parts
     */
    public function newMessages(Request $request, array $parts): Response
    {
        $user = $this->pages->user();
        $other = $this->services->users()->findByUsername(MemberPages::nameFrom($parts));
        if ($other === null) {
            return Response::json(['messages' => []], 404);
        }
        $afterId = max(0, (int) $request->queryValue('after', '0'));
        $messages = $this->services->messages()->open($user->id, $other->id, $afterId, $this->services->now);

        return Response::json(['messages' => array_map(fn (array $message): array => [
            'id' => $message['id'],
            'mine' => $message['from_user_id'] === $user->id,
            'body' => $message['body'],
            'created_at' => $message['created_at'],
        ], $messages)])->withHeader('Cache-Control', 'no-store');
    }

    /**
     * @param array<string, string> $parts
     */
    public function send(Request $request, array $parts): Response
    {
        $user = $this->pages->user();
        $name = MemberPages::nameFrom($parts);
        $other = $this->services->users()->findByUsername($name);
        $path = '/messages/' . rawurlencode($name);
        try {
            if ($other === null) {
                throw new ActionRefused('messages.unknown_player');
            }
            $this->services->messages()->send($user->id, $other->id, $request->formValue('body'), $this->services->now);
        } catch (ActionRefused $refusal) {
            return $this->pages->refused($request, $other === null ? '/messages' : $path, $refusal);
        }

        return Response::redirect($request->basePath . $path . '#newest');
    }
}
