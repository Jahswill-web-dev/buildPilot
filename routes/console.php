<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Symfony\Component\Process\Process;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('ai:diagnose', function () {
    $apiKey = (string) config('services.openai.api_key', '');
    $model = (string) config('services.openai.checklist_model', 'gpt-5-nano');
    $nodeBinary = (string) config('services.openai.node_binary', 'node');
    $scriptPath = (string) config('services.openai.roadmap_script', base_path('resources/js/ai/generate-roadmap.mjs'));

    $this->line('AI diagnostic');
    $this->line('OPENAI_API_KEY configured: '.($apiKey !== '' ? 'yes' : 'no'));
    $this->line('OPENAI_API_KEY length: '.strlen($apiKey));
    $this->line('OPENAI_CHECKLIST_MODEL: '.$model);
    $this->line('AI_NODE_BINARY: '.$nodeBinary);
    $this->line('Roadmap script exists: '.(is_file($scriptPath) ? 'yes' : 'no'));

    $nodeVersion = new Process([$nodeBinary, '--version'], base_path());
    $nodeVersion->run();
    $this->line('Node version exit code: '.$nodeVersion->getExitCode());
    $this->line('Node version output: '.trim($nodeVersion->getOutput().$nodeVersion->getErrorOutput()));

    $packageCheck = new Process([
        $nodeBinary,
        '-e',
        "import('@langchain/openai').then(() => console.log('langchain openai ok')).catch((error) => { console.error(error.stack || error.message || error); process.exit(1); })",
    ], base_path());
    $packageCheck->run();
    $this->line('LangChain import exit code: '.$packageCheck->getExitCode());
    $this->line('LangChain import output: '.trim($packageCheck->getOutput().$packageCheck->getErrorOutput()));

    if ($apiKey === '' || ! is_file($scriptPath)) {
        return (int) ($apiKey === '' || ! is_file($scriptPath));
    }

    $roadmapCheck = new Process([$nodeBinary, $scriptPath], base_path());
    $roadmapCheck->setEnv([
        'OPENAI_API_KEY' => $apiKey,
        'OPENAI_CHECKLIST_MODEL' => $model,
    ]);
    $roadmapCheck->setTimeout((int) config('services.openai.roadmap_timeout', 120));
    $roadmapCheck->setInput(json_encode([
        'idea_title' => 'AI diagnostic',
        'idea_description' => 'A tiny production diagnostic to verify the OpenAI roadmap bridge.',
        'model' => $model,
    ], JSON_THROW_ON_ERROR));
    $roadmapCheck->run();

    $this->line('Roadmap bridge exit code: '.$roadmapCheck->getExitCode());
    $this->line('Roadmap bridge stderr: '.trim($roadmapCheck->getErrorOutput()));

    $output = trim($roadmapCheck->getOutput());
    $decoded = $output !== '' ? json_decode($output, true) : null;

    if (is_array($decoded)) {
        $stageErrors = array_filter((array) ($decoded['stage_errors'] ?? []));

        $this->line('Roadmap bridge returned JSON: yes');
        $this->line('Roadmap stage errors: '.json_encode($stageErrors, JSON_UNESCAPED_SLASHES));
        $this->line('Roadmap has target user: '.(! empty($decoded['target_user']) ? 'yes' : 'no'));
    } else {
        $this->line('Roadmap bridge returned JSON: no');
        $this->line('Roadmap bridge output: '.$output);
    }

    return $roadmapCheck->isSuccessful() && is_array($decoded) && empty($stageErrors ?? []) ? 0 : 1;
})->purpose('Diagnose the production AI roadmap bridge without printing secrets');
