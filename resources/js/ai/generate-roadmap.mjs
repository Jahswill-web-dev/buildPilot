import { readFile } from 'node:fs/promises';
import { resolve } from 'node:path';
import { ChatOpenAI } from '@langchain/openai';
import { z } from 'zod';

const input = await readJsonFromStdin();
const promptDir = resolve(process.cwd(), 'resources/prompts/roadmap');

const model = new ChatOpenAI({
    apiKey: process.env.OPENAI_API_KEY,
    model: input.model || process.env.OPENAI_CHECKLIST_MODEL || 'gpt-5-nano',
});

const roadmapSchema = z.object({
    target_user: z.object({
        user_type: z.string().trim().min(1),
        main_problem: z.string().trim().min(1),
        current_workaround: z.string().trim().min(1),
        why_they_care: z.string().trim().min(1),
    }),
    problem_statement: z.string().trim().min(1),
    desired_outcome: z.string().trim().min(1),
    core_features: z.array(z.object({
        feature: z.string().trim().min(1),
        reason: z.string().trim().min(1),
    })).min(3).max(8),
    mvp_scope: z.object({
        must_have: z.array(z.string().trim().min(1)).min(1),
        nice_to_have: z.array(z.string().trim().min(1)).min(1),
        later: z.array(z.string().trim().min(1)).min(1),
    }),
    phases: z.array(z.object({
        title: z.string().trim().min(1),
        description: z.string().trim().min(1),
        primary_category: z.enum(['validation', 'product', 'marketing']),
        included_categories: z.array(z.enum(['validation', 'product', 'marketing'])).min(1),
        goal: z.string().trim().min(1),
        success_criteria: z.string().trim().min(1),
        order: z.number().int().min(1),
    })).min(5).max(8),
    checklist: z.array(z.object({
        title: z.string().trim().min(1),
        description: z.string().trim().min(1),
    })).length(7),
});

const roadmap = {
    target_user: null,
    problem_statement: null,
    desired_outcome: null,
    core_features: null,
    mvp_scope: null,
    phases: null,
    checklist: null,
    stage_errors: {},
};

const generatedRoadmap = await runRoadmapStage();

if (generatedRoadmap) {
    roadmap.target_user = generatedRoadmap.target_user;
    roadmap.problem_statement = generatedRoadmap.problem_statement;
    roadmap.desired_outcome = generatedRoadmap.desired_outcome;
    roadmap.core_features = generatedRoadmap.core_features;
    roadmap.mvp_scope = generatedRoadmap.mvp_scope;
    roadmap.phases = generatedRoadmap.phases;
    roadmap.checklist = generatedRoadmap.checklist;
}

process.stdout.write(JSON.stringify(roadmap));

async function runRoadmapStage() {
    try {
        const prompt = await buildRoadmapPrompt();
        const structuredModel = model.withStructuredOutput(roadmapSchema, {
            name: 'startup_roadmap',
        });

        return await structuredModel.invoke(prompt);
    } catch (error) {
        roadmap.stage_errors.roadmap = error instanceof Error ? error.message : String(error);

        return null;
    }
}

async function buildRoadmapPrompt() {
    const [profilePrompt, coreFeaturesPrompt, mvpScopePrompt, phasesPrompt, checklistPrompt] = await Promise.all([
        readFile(resolve(promptDir, 'profile.md'), 'utf8'),
        readFile(resolve(promptDir, 'core-features.md'), 'utf8'),
        readFile(resolve(promptDir, 'mvp-scope.md'), 'utf8'),
        readFile(resolve(promptDir, 'phases.md'), 'utf8'),
        readFile(resolve(promptDir, 'checklist.md'), 'utf8'),
    ]);

    return `Generate the full startup roadmap in one response.

Use the following section requirements as the source of truth:

Profile requirements:
${profilePrompt}

Core feature requirements:
${coreFeaturesPrompt}

MVP scope requirements:
${mvpScopePrompt}

Phase requirements:
${phasesPrompt}

Checklist requirements:
${checklistPrompt}

Startup idea:
${JSON.stringify(ideaContext(), null, 2)}

Return valid JSON only.`;
}

function ideaContext() {
    return {
        title: input.idea_title,
        description: input.idea_description,
    };
}

async function readJsonFromStdin() {
    const chunks = [];

    for await (const chunk of process.stdin) {
        chunks.push(chunk);
    }

    return JSON.parse(Buffer.concat(chunks).toString('utf8'));
}
