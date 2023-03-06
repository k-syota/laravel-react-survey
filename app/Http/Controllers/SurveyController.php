<?php

namespace App\Http\Controllers;

use App\Http\Requests\SurveyStoreRequest;
use App\Http\Requests\SurveyUpdateRequest;
use App\Http\Resources\SurveyResource;
use App\Models\Survey;
use App\Models\SurveyQuestion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rules\Enum;

class SurveyController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $user = $request->user();
        return SurveyResource::collection(
            Survey::where('user_id', $user->id)
            ->orderBy('created_at','desc')
            ->paginate(10)
        );
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(SurveyStoreRequest $request)
    {
        $data = $request->validated();
        if(isset($data['image'])){
            $relativePath = $this->saveImage($data['image']);
            $data['image'] = $relativePath;
        }
        $survey = Survey::create($data);

        foreach($data['questions'] as $question){
            $question['survey_id'] = $survey->id;
            $this->createQuestion($question);
        }

        return new SurveyResource($survey);
    }

    /**
     * @param Survey $survey
     * @param Request $request
     *
     */
    public function show(Survey $survey, Request $request)
    {
        $user = $request->user();
        if($user->id !== $survey->user_id){
            return abort(403,'Unauthorized action');
        }
        return new SurveyResource($survey);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(SurveyUpdateRequest $request, Survey $survey)
    {
        $data = $request->validated();
        if(isset($data['image'])){
            $relativePath = $this->saveImage($data['image']);
            $data['image'] = $relativePath;
        }

        if($survey->image){
            $absolutePath = public_path($survey->image);
            File::delete($absolutePath);
        }

        $survey->update($data);


        $existingIds = $survey->questions()->pluck('id')->toArray();
        $newIds = Arr::pluck($data['questions'],'id');
        $toDelete = array_diff($existingIds,$newIds);
        $toAdd = array_diff($newIds,$existingIds);

        SurveyQuestion::delete($toDelete);

        foreach($data['questions'] as $question){
            if(in_array($question['id'],$toAdd)){
                $question['survey_id'] = $survey->id;
                $this->createQuestion($question);
            }
        }

        $questionMap = collect($data['question'])->keyBy('id');
        foreach($survey->questions as $question){
            if(isset($questionMap[$question->id])){
                $this->updateQUestion($question,$questionMap[$question->id]);
            }
        }

        return new SurveyResource($survey);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }

    /**
     * Save image in local filesystem and return save image path
     *
     * @param [type] $image
     * @throws \Exception
     * @author   <email@email.com>
     */
    private function saveImage($image)
    {
        if (preg_match('/^data:image\/(\w+);base64,/', $image, $type)) {
            $image = substr($image, strpos($image, ',') +1);
            $type = strtolower($type[1]);
            if(!in_array($type, ['jpg','jpeg','gif','png'])){
                throw new \Exception('invalid image type');
            }
            $image = str_replace(' ','+',$image);
            $image = base64_decode($image);

            if($image === false){
                throw new \Exception('base64_decode failed');
            }
        }else{
            throw new \Exception('did not match image data URI with image data');
        }

        $dir = 'images/';
        $file = Str::random(). ','. $type;
        $absolutePath = public_path($dir);
        $relativePath = $dir . $file;
        if(!File::exists($absolutePath)){
            File::makeDirectory($absolutePath,0755,true);
        }
        file_put_contents($relativePath, $image);

        return $relativePath;
    }

    /**
     * create question and return
     *
     * @param [type] $data
     * @return mixed
     * @throws \Exception
     */
    private function createQuestion($data)
    {
        if(is_array($data['data'])){
            $data['data'] = json_encode($data['data']);
        }
        $validator = Validator::make($data,[
            'question' => 'required|string',
            'type' => ['required', new Enum(QuestionTypeEnum::class)],
            'description' => 'nullable|string',
            'data' => 'present',
            'survey_id' => 'exists:App\Model\Survey,id'
        ]);

        return SurveyQuestion::create($validator->validated());
    }
}
