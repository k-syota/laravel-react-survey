<?php

namespace App\Http\Controllers;

use App\Http\Requests\SurveyStoreRequest;
use App\Http\Requests\SurveyUpdateRequest;
use App\Http\Resources\SurveyResource;
use App\Models\Survey;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

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
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(SurveyUpdateRequest $request, string $id)
    {
        //
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
}
