<?php

namespace App\Http\Controllers\Api;

use App\Models\Flow;
use App\Models\ResultTemplate;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ResultTemplateController extends Controller
{
    public function index($flowId)
    {
        $flow = Flow::findOrFail($flowId);
        $this->ensureOwnsFlow($flow);

        return response()->json($flow->resultTemplates);
    }

    public function store($flowId)
    {
        $flow = Flow::findOrFail($flowId);
        $this->ensureOwnsFlow($flow);

        request()->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'image_url' => 'nullable|url',
            'min_score' => 'required|integer|min:0',
            'max_score' => 'nullable|integer|min:0',
            'cta_text' => 'nullable|string|max:255',
            'cta_url' => 'nullable|url',
            'order' => 'nullable|integer|min:0',
        ]);

        $template = $flow->resultTemplates()->create([
            'title' => request('title'),
            'content' => request('content'),
            'image_url' => request('image_url'),
            'min_score' => request('min_score'),
            'max_score' => request('max_score'),
            'cta_text' => request('cta_text'),
            'cta_url' => request('cta_url'),
            'order' => request('order', 0),
        ]);

        return response()->json($template, Response::HTTP_CREATED);
    }

    public function update($flowId, $templateId)
    {
        $flow = Flow::findOrFail($flowId);
        $this->ensureOwnsFlow($flow);

        $template = $flow->resultTemplates()->findOrFail($templateId);

        request()->validate([
            'title' => 'sometimes|string|max:255',
            'content' => 'sometimes|string',
            'image_url' => 'nullable|url',
            'min_score' => 'sometimes|integer|min:0',
            'max_score' => 'nullable|integer|min:0',
            'cta_text' => 'nullable|string|max:255',
            'cta_url' => 'nullable|url',
            'order' => 'nullable|integer|min:0',
        ]);

        $template->update(request()->only([
            'title', 'content', 'image_url', 'min_score', 
            'max_score', 'cta_text', 'cta_url', 'order'
        ]));

        return response()->json($template);
    }

    public function destroy($flowId, $templateId)
    {
        $flow = Flow::findOrFail($flowId);
        $this->ensureOwnsFlow($flow);

        $template = $flow->resultTemplates()->findOrFail($templateId);
        $template->delete();

        return response()->json(null, Response::HTTP_NO_CONTENT);
    }

    private function ensureOwnsFlow(Flow $flow): void
    {
        if ($flow->user_id !== auth()->id()) {
            abort(Response::HTTP_FORBIDDEN, 'Not authorized to access this flow.');
        }
    }
}
