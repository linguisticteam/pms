<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Medias_model extends CI_Model
{
    public function get_medias($id = NULL)
    {
        if ( $id != NULL )
        {
            $this->db->where('id',$id);
        }

        $query = $this->db->get('medias');

        $appended_medias_array = array();

        if($query->num_rows() > 0)
        {
            $appended_medias_array = $query->result();
        }
        else
        {
            return FALSE;
        }

        if ($query->num_rows() > 1)
        {
            return $appended_medias_array;
        }
        else
        {
            return $appended_medias_array[0];
        }
    }
    
//    public function 

    public function get_videos_transcribing($team_id)
    {
        $where = 'project_language_id ='.$team_id.' AND type ='.MEDIA_TYPE_VIDEO.' AND state ='.STATE_OPEN_FOR_TRANSCRIPTION.' OR state='.STATE_OPEN_FOR_FIRST_PROOFREADING.' OR state='.STATE_TIMESTAMP_SHIFTING.
                 ' OR state='.STATE_FINAL_PROOFREADING.' OR state='.STATE_WAINTING_FINAL_REVIEW;
        $this->db->where($where);

        return $this->get_medias();
    }

    public function get_videos_open_for_translation($team_id)
    {
        $where = 'project_language_id ='.$team_id.' AND type ='.MEDIA_TYPE_VIDEO.' AND state ='.STATE_LOCKED_AND_AVAILABLE;
        $this->db->where($where);

        return $this->get_medias();
    }

    public function get_videos_in_progress($team_id)
    {
        $where = 'project_language_id ='.$team_id.' AND type ='.MEDIA_TYPE_VIDEO.' AND state ='.STATE_OPEN_FOR_TRANSLATION.' OR state='.STATE_OPEN_FOR_PROOFREADING;
        $this->db->where($where);

        return $this->get_medias();
    }

    public function get_videos_ready_to_post($team_id)
    {
        $where = 'project_language_id ='.$team_id.' AND type ='.MEDIA_TYPE_VIDEO.' AND state ='.STATE_FINALIZED;
        $this->db->where($where);

        return $this->get_medias();
    }

    public function get_videos_posted($team_id)
    {
        $where = 'project_language_id ='.$team_id.' AND type ='.MEDIA_TYPE_VIDEO.' AND state ='.STATE_POSTED;
        $this->db->where($where);

        return $this->get_medias();
    }

    public function get_videos_repository($team_id)
    {
        $where = 'project_language_id ='.$team_id.' AND type ='.MEDIA_TYPE_VIDEO.' AND state ='.STATE_REPOSITORY;
        $this->db->where($where);

        return $this->get_medias();
    }

    public function get_videos_on_hold($team_id)
    {
        $where = 'project_language_id ='.$team_id.' AND type ='.MEDIA_TYPE_VIDEO.' AND state ='.STATE_ON_HOLD. ' OR state='.STATE_UNDER_ERROR_REVIEW. ' OR state='.STATE_UNDER_ERROR_REPAIR;
        $this->db->where($where);

        return $this->get_medias();
    }

    public function insert_video($data=NULL)
    {
        if ($data!=NULL)
        {
            $this->db->insert('medias',$data);

            $team = $this->session->userdata('teamdata');
            $this->session->set_userdata('video_added','Video added successfully');

            redirect('languages/'.$team->shortname.'/videos/add');
        }
    }

    public function update_video($data=NULL,$condition=NULL)
    {
        if ($data!=NULL && $condition!=NULL)
        {
            $this->db->update('medias',$data,$condition);

            $team = $this->session->userdata('teamdata');
            $this->session->set_userdata('video_edited','Video edited successfully');

            redirect('languages/'.$team->shortname.'/videos/edit/'.$condition['id']);
        }
    }

    public function register_function($media_id, $function, $user_id=NULL)
    {
        //get media
        $media = $this->get_medias($media_id);

        //get team
        $user_language = $this->session->userdata('user_language');
        $user_role = $this->session->userdata('user_role');

        //get user
        if ($user_id==NULL)
        {
            $user_id = $this->session->userdata('user_id');
        }

        //check if user belongs to this team
        if ($user_language==$media->project_language_id || $user_role >= USER_ROLE_COORDINATION)
        {
            $this->workgroups_model->register_workgroup($media_id, $user_id, $function);
        }
    }

    public function go_to_stage($media_id, $state, $inc)
    {
        if (is_numeric($inc))
        {
            $data = array('state'=>$state+$inc);
            $this->db->where('id',$media_id);
            $this->db->update('medias',$data);
        }
    }

    public function release_translations($media_id)
    {
        // get teams
        $teams = $this->language_teams_model->get_language_teams();

        $media = $this->get_medias($media_id);

        foreach($teams as $team):
            if ($team->id != $media->original_language_id)
            {
                $data = array(
                            'title' => $media->title,
                            'description' => $media->description,
                            'originator' => $media->originator,
                            'producer' => $media->producer,
                            'original_language_id' => $media->original_language_id,
                            'project_language_id' => $team->id,
                            'state' => STATE_OPEN_FOR_TRANSLATION,
                            'type' => $media->type,
                            'subtype' => $media->subtype,
                            'category' => $media->category,
                            'parent_id' => $media->id,
                            'priority' => $media->priority,
                            'working_location' => $media->working_location,
                            'original_location' => $media->original_location,
                            'publish_location' => '',
                            'repo_storage_location' => $media->repo_storage_location,
                            'repo_distribution_location' => $media->repo_distribution_location,
                            'duration' => $media->duration,
                            'number_of_words' => $media->number_of_words,
                            'date_added' => $media->date_added,
                            'date_finished' => '',
                            'forum_thread' => $media->forum_thread,
                            'comments' => '',
                            'notes' => $media->notes,
                         );

                $this->db->insert('medias',$data);
            }
        endforeach;
    }
}