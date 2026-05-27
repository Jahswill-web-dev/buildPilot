import { readFile } from 'node:fs/promises';
import { resolve } from 'node:path';
import { ChatOpenAI } from '@langchain/openai';
import { z } from 'zod';

const input = await readJsonFromStdin();
const prompt = await readFile(resolve(process.cwd(), 'resources/prompts/roadmap/phase-tasks.md'), 'utf8');

const model = new ChatOpenAI({
    apiKey: process.env.OPENAI_API_KEY,
    model: input.model || process.env.OPENAI_CHECKLIST_MODEL || 'gpt-5-nano',
});

const categorySchema = z.enum(['validation', 'product', 'marketing']);
const taskTypeSchema = z.enum([
    'user_interview',
    'assumption_test',
    'competitor_research',
    'survey',
    'feedback_review',
    'feature_planning',
    'ux_flow',
    'implementation',
    'testing',
    'deployment',
    'positioning',
    'landing_page_copy',
    'content_creation',
    'community_distribution',
    'outreach',
    'analytics',
    'other',
]);

const phaseTasksSchema = z.object({
    phase: z.object({
        title: z.string().trim().min(1),
        goal: z.string().trim().min(1),
        summary: z.string().trim().min(1),
    }),
    tasks: z.array(z.object({
        title: z.string().trim().min(1),
        category: categorySchema,
        task_type: taskTypeSchema,
        description: z.string().trim().min(1),
        why_it_matters: z.string().trim().min(1),
        steps: z.array(z.string().trim().min(1)).min(1),
        definition_of_done: z.string().trim().min(1),
        deliverable: z.string().trim().min(1),
        priority: z.enum(['high', 'medium', 'low']),
        estimated_time_minutes: z.number().int().min(5).max(480),
        order: z.number().int().min(1),
        interview_questions: z.array(z.string().trim().min(1)),
        research_checklist: z.array(z.string().trim().min(1)),
        copy_examples: z.array(z.string().trim().min(1)),
        outreach_message: z.string(),
        implementation_notes: z.array(z.string().trim().min(1)),
        acceptance_criteria: z.array(z.string().trim().min(1)),
        metrics_to_track: z.array(z.string().trim().min(1)),
    })).min(5).max(10),
});

const structuredModel = model.withStructuredOutput(phaseTasksSchema, {
    name: 'phase_tasks',
});

const result = await structuredModel.invoke(`${prompt}

Use this phase and product context:
${JSON.stringify(phaseContext(), null, 2)}

Return valid JSON only.`);

process.stdout.write(JSON.stringify(result));

function phaseContext() {
    return {
        product_description: input.product_description,
        target_users: input.target_users,
        problem_statement: input.problem_statement,
        desired_outcome: input.desired_outcome,
        core_features: input.core_features,
        mvp_scope: input.mvp_scope,
        roadmap_phase_title: input.phase_title,
        roadmap_phase_description: input.phase_description,
        phase_goal: input.phase_goal,
        phase_primary_category: input.phase_primary_category,
        phase_included_categories: input.phase_included_categories,
        previously_completed_tasks: input.completed_tasks,
    };
}

async function readJsonFromStdin() {
    const chunks = [];

    for await (const chunk of process.stdin) {
        chunks.push(chunk);
    }

    return JSON.parse(Buffer.concat(chunks).toString('utf8'));
}
