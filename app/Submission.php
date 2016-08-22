<?php

namespace Judgement;

use Illuminate\Database\Eloquent\Model;
use Judgement\Judgement;

/**
 * Judgement\Submission
 *
 * @property-read \Judgement\Problem $problem
 * @property-read \Judgement\User $user
 * @mixin \Eloquent
 * @property integer $id
 * @property integer $problem_id
 * @property integer $group_id
 * @property integer $user_id
 * @property string $language
 * @property string $status
 * @property string $submitted_at
 * @property string $filename
 * @method static \Illuminate\Database\Query\Builder|\Judgement\Submission whereId($value)
 * @method static \Illuminate\Database\Query\Builder|\Judgement\Submission whereProblemId($value)
 * @method static \Illuminate\Database\Query\Builder|\Judgement\Submission whereGroupId($value)
 * @method static \Illuminate\Database\Query\Builder|\Judgement\Submission whereUserId($value)
 * @method static \Illuminate\Database\Query\Builder|\Judgement\Submission whereLanguage($value)
 * @method static \Illuminate\Database\Query\Builder|\Judgement\Submission whereStatus($value)
 * @method static \Illuminate\Database\Query\Builder|\Judgement\Submission whereSubmittedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\Judgement\Submission whereFilename($value)
 */
class Submission extends Model
{
    public function problem()
    {
        return $this->belongsTo('Judgement\Problem');
    }

    public function user()
    {
        return $this->belongsTo('Judgement\User');
    }

    function compile()
    {
        $compiler = Language::compiler($this->language);
        $file_loc = \Storage::disk()->getDriver()->getAdapter()->getPathPrefix() . $this->filename;
        $command = $compiler . ' ' . $file_loc . ' 2>&1';
        exec($command, $output, $status);
        return $status;
    }

    public function run()
    {
        $this->compile();

        exec('isolate --cg --init');

        $lang = $this->language;

        $executor = Language::executor($lang);
        $file_name = pathinfo($this->filename, PATHINFO_FILENAME);
        $file_loc = \Storage::disk()->getDriver()->getAdapter()->getPathPrefix() . $file_name . Language::ext($lang);
        $new_loc_dir = '1' . '/' . $this->user->id;
        $new_loc_file = "/tmp/box/0/box/" . $new_loc_dir . '/' . $file_name . Language::ext($lang);
        $isolate_box_loc = "/tmp/box/0/box/" . $new_loc_dir;

        mkdir($isolate_box_loc, 0777, true);
        rename($file_loc, $new_loc_file);

        $command = 'isolate --cg -p100 --run -- ' . $executor . ' -classpath ' . $new_loc_dir . ' ' . pathinfo($this->filename, PATHINFO_FILENAME) . ' 2>&1';
        exec($command, $output, $status);
        var_dump($output);

    }
}