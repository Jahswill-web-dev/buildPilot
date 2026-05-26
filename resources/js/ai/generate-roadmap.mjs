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

const schemas = {
    profile: z.object({
        target_user: z.object({
            user_type: z.string().trim().min(1),
            main_problem: z.string().trim().min(1),
            current_workaround: z.string().trim().min(1),
            why_they_care: z.string().trim().min(1),
        }),
        problem_statement: z.string().trim().min(1),
        desired_outcome: z.string().trim().min(1),
    }),
    coreFeatures: z.object({
        core_features: z.array(z.object({
            feature: z.string().trim().min(1),
            reason: z.string().trim().min(1),
        })).min(3).max(8),
    }),
    mvpScope: z.object({
        mvp_scope: z.object({
            must_have: z.array(z.string().trim().min(1)).min(1),
            nice_to_have: z.array(z.string().trim().min(1)).min(1),
            later: z.array(z.string().trim().min(1)).min(1),
        }),
    }),
    checklist: z.object({
        checklist: z.array(z.object({
            title: z.string().trim().min(1),
            description: z.string().trim().min(1),
        })).length(7),
    }),
};

const roadmap = {
    target_user: null,
    problem_statement: null,
    desired_outcome: null,
    core_features: null,
    mvp_scope: null,
    checklist: null,
    stage_errors: {},
};

const profile = await runStage('profile', 'profile.md', schemas.profile, {
    idea: ideaContext(),
});

if (profile) {
    roadmap.target_user = profile.target_user;
    roadmap.problem_statement = profile.problem_statement;
    roadmap.desired_outcome = profile.desired_outcome;
}

const coreFeatures = await runStage('core_features', 'core-features.md', schemas.coreFeatures, {
    idea: ideaContext(),
    profile,
});

if (coreFeatures) {
    roadmap.core_features = coreFeatures.core_features;
}

const mvpScope = await runStage('mvp_scope', 'mvp-scope.md', schemas.mvpScope, {
    idea: ideaContext(),
    profile,
    core_features: coreFeatures?.core_features ?? null,
});

if (mvpScope) {
    roadmap.mvp_scope = mvpScope.mvp_scope;
}

const checklist = await runStage('checklist', 'checklist.md', schemas.checklist, {
    idea: ideaContext(),
    profile,
    core_features: coreFeatures?.core_features ?? null,
    mvp_scope: mvpScope?.mvp_scope ?? null,
});

if (checklist) {
    roadmap.checklist = checklist.checklist;
}

process.stdout.write(JSON.stringify(roadmap));

async function runStage(stageName, promptFile, schema, context) {
    try {
        const prompt = await buildPrompt(promptFile, context);
        const structuredModel = model.withStructuredOutput(schema, {
            name: `startup_roadmap_${stageName}`,
        });

        return await structuredModel.invoke(prompt);
    } catch (error) {
        roadmap.stage_errors[stageName] = error instanceof Error ? error.message : String(error);

        return null;
    }
}

async function buildPrompt(promptFile, context) {
    const promptText = await readFile(resolve(promptDir, promptFile), 'utf8');

    return `${promptText}

Startup idea and available roadmap context:
${JSON.stringify(context, null, 2)}

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
