<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;

class GitHubWebhookController extends Controller
{
    public function handle(Request $request)
    {
        $secret = env('GITHUB_WEBHOOK_SECRET', '');
        $sig256 = $request->header('X-Hub-Signature-256');
        $sig1 = $request->header('X-Hub-Signature');
        $payload = $request->getContent();
        $valid = false;
        if ($secret && $sig256) {
            $expected256 = 'sha256=' . hash_hmac('sha256', $payload, $secret);
            $valid = hash_equals($expected256, $sig256);
        }
        if (!$valid && $secret && $sig1) {
            $expected1 = 'sha1=' . hash_hmac('sha1', $payload, $secret);
            $valid = hash_equals($expected1, $sig1);
        }
        if (!$secret || !$valid) {
            return response()->json(['status' => false, 'message' => 'invalid signature'], 403);
        }
        if (!filter_var(env('DEPLOY_ENABLED', false), FILTER_VALIDATE_BOOLEAN)) {
            return response()->json(['status' => false, 'message' => 'deploy disabled'], 400);
        }
        $ref = data_get(json_decode($payload, true), 'ref', '');
        if ($ref !== 'refs/heads/main') {
            return response()->json(['status' => true, 'message' => 'ignored ref'], 200);
        }
        $appPath = env('DEPLOY_APP_PATH', base_path());
        $php = env('DEPLOY_PHP_BINARY', 'php');
        $composer = env('DEPLOY_COMPOSER_BINARY', 'composer');
        $cmd = "cd {$appPath} && git fetch --all && git reset --hard origin/main && {$composer} install --no-dev --optimize-autoloader && {$php} artisan cache:clear && {$php} artisan config:clear && {$php} artisan route:clear && {$php} artisan view:clear && {$php} artisan config:cache && {$php} artisan route:cache && {$php} artisan view:cache && {$php} artisan optimize";
        $process = Process::fromShellCommandline($cmd);
        $process->setTimeout(600);
        $process->run();
        Log::info('deploy', ['cmd' => $cmd, 'output' => $process->getOutput(), 'error' => $process->getErrorOutput(), 'exit' => $process->getExitCode()]);
        if (!$process->isSuccessful()) {
            return response()->json(['status' => false, 'message' => 'deploy failed'], 500);
        }
        return response()->json(['status' => true, 'message' => 'deployed'], 200);
    }
}
