<?php

namespace App\Http\Controllers;

use App\Answer;
use App\Question;
use App\QuestionCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class QuestionController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $questions = Question::with('rightAnswer')->get();

        return view('admin.questions.index', compact('questions'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('admin.questions.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $this->validate(
            $request,
            [
                'categories' => 'required|array',
                'content' => 'required',
                'explain' => 'required',
                'right_answer_id' => 'required',
                'wrong_answer_ids' => 'required|array',
                'wrong_answer_ids.*' => 'different:right_answer_id'
            ]
        );

        $createData = $request->only([
            'content',
            'explain',
            'right_answer_id',
            'wrong_answer_ids'
        ]);


        $createData['wrong_answer_ids'] = json_encode($request->wrong_answer_ids);
        $categoryIds = $request->categories;
        DB::transaction(function () use ($createData, $categoryIds) {
            $question = Question::query()->create($createData);
            foreach ($categoryIds as $categoryId) {
                QuestionCategory::query()->create([
                    'question_id' => $question->id,
                    'category_id' => $categoryId
                ]);
            }
        });


        return redirect(route('questions.index'))->with('success', 'Created successfully!');
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Question  $question
     * @return \Illuminate\Http\Response
     */
    public function show(Question $question)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Question  $question
     * @return \Illuminate\Http\Response
     */
    public function edit(Question $question)
    {
        $question = $question->load('categories');
        $wrongAnswers = Answer::query()->whereIn('id', json_decode($question->wrong_answer_ids))->get();
        return view('admin.questions.edit', compact('question', 'wrongAnswers'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Question  $question
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Question $question)
    {
        $this->validate(
            $request,
            [
                'content' => 'required',
                'explain' => 'required',
                'right_answer_id' => 'required',
                'wrong_answer_ids' => 'required|array',
                'wrong_answer_ids.*' => 'different:right_answer_id'
            ]
        );

        $updateData = $request->only([
            'content',
            'explain',
            'right_answer_id',
            'wrong_answer_ids'
        ]);

        $updateData['wrong_answer_ids'] = json_encode($updateData['wrong_answer_ids']);

        $categoryIds = $request->categories;

        DB::transaction(function () use ($updateData, $categoryIds, $question) {
            $question->update($updateData);
            foreach ($categoryIds as $categoryId) {
                QuestionCategory::query()->updateOrCreate([
                    'question_id' => $question->id,
                    'category_id' => $categoryId
                ]);
            }
        });
        return redirect(route('questions.index'))->with('success', 'Updated successfully!');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Question  $question
     * @return \Illuminate\Http\Response
     */
    public function destroy(Question $question)
    {
        try {
            $question->delete();
            return redirect(route('questions.index'))->with('success', 'Deleted successfully!');
        } catch (\Exception $e) {
            return redirect(route('questions.index'))->with('error', 'Deleted error!');
        }
    }
}
