<?php namespace Pensoft\Externaldocuments\Components;

use Backend\Facades\BackendAuth;
use Cms\Classes\ComponentBase;
use Illuminate\Support\Facades\DB;
use RainLab\User\Facades\Auth;

/**
 * Documents Component
 */
class Documents extends ComponentBase
{
    public function componentDetails()
    {
        return [
            'name' => 'Documents Component',
            'description' => 'No description provided yet...'
        ];
    }

    public function onRun()
    {
        $this->page['external_documents'] = DB::connection($this->property('externalLibrary'))
            ->table('pjs.documents as d')
            ->selectRaw('d.id as document_id, d.name->>\'en\' as name, d.subject_categories, d.panel_duedate,
            (SELECT aggr_concat_coma(a.author_name)
							FROM (
								SELECT (user_full_name(du.first_name->>\'en\', \'\', du.last_name->>\'en\')) as author_name
								FROM pjs.document_users du
								JOIN public.usr u ON u.id = du.uid
								WHERE du.document_id = d.id AND
									  du.role_id IN (11, 15) AND
									  du.state_id = 1
								ORDER BY du.ord
							) a
						) as authors_list,
						(SELECT aggr_concat_coma(s.name)
							FROM (
								SELECT name
								FROM subject_categories sc
								WHERE sc.id = ANY(d.subject_categories)
								ORDER BY id desc
								LIMIT 1
							) s
						) as subjects_list
            ')
            ->where('d.journal_id', (int)$this->property('journal'))
            ->where('d.state_id', 3) //in review
            ->where('d.panel_duedate', '>=', DB::raw('now()')) // added duedate condition
            ->orderBy('d.id', 'desc')
            ->paginate((int)$this->property('paging'));

        $loggedIn = Auth::check();
        if ( $loggedIn ){
            $user = Auth::getUser();
            $arphaUserData = DB::connection($this->property('externalLibrary'))
                ->select('SELECT autolog_hash
                                FROM usr
                                WHERE id = ' . (int)$user->arpha_id . ';');
            $this->page['autolog_hash'] = $arphaUserData[0]->autolog_hash;
        }
    }

    public function defineProperties(){
        return [
            'externalLibrary' => [
                'title' => 'Select external library',
                'type' => 'dropdown',
                'default' => 'arpha'
            ],
            'paging' => [
                'title' => 'Select records per page',
                'type' => 'integer',
                'default' => 10
            ],
            'journal' => [
                'title' => 'Select Journal',
                'type' => 'dropdown',
                'default' => 1 //journalId default BDJ
            ],
        ];
    }

    public function getExternalLibraryOptions()
    {
        return [
            'arpha' => 'ARPHA', //database connection
        ];
    }

    public function getJournalOptions()
    {
        // array key -> journal id, value-> journal name
        return [
            1 => 'SOLO',
        ];
    }

    public function encryptStringUrl($documentId, $userId){

        $string = 'document_id=' . (int)$documentId . '&user_id=' . (int)$userId . '&action=invite_and_review_panel';

        $encrypted_string = openssl_encrypt($string, getenv('OPENSSL_CYPHERING'), getenv('OPENSSL_ENCRYPTION_KEY'), 0, getenv('OPENSSL_ENCRYPTION_IV'));

        $url = getenv('ARPHA_DOMAIN').'lib/ajax_srv/document_srv.php?p='.urlencode($encrypted_string);

        return $url;


    }



    public function calculatePanelDueDateDays($pDate){

        $nowDate = \DateTime::createFromFormat('Y-m-d', date("Y-m-d"))->setTime(0, 0);
        $duedate = \DateTime::createFromFormat('Y-m-d H:i:s.u', $pDate);
        if(!$duedate) {
            $duedate = \DateTime::createFromFormat('Y-m-d H:i:s', $pDate)->setTime(3, 0);
        } else {
            $duedate->setTime(3, 0);
        }

        $duedateDate = new \DateTime($duedate->format("Y-m-d"));
        if($nowDate <= $duedateDate) {
            $lResArr['flag'] = 1;
            $lResArr['datediff'] = $nowDate->diff($duedate)->format('%a');
        } else {
            $lResArr['flag'] = 2;
            $lResArr['datediff'] = $duedateDate->diff($nowDate)->format('%a');
        }

        if($lResArr['datediff'] > 0) {
            $lResArr['datediff'] .= ' day' . $this->plural($lResArr['datediff']);
        } else {
            $nowDate = \DateTime::createFromFormat('Y-m-d', date("Y-m-d"));
            if($nowDate > $duedate) {
                $lResArr['flag'] = 2;
            } else {
                $lResArr['flag'] = 1;
            }
            $lResArr['datediff'] = $nowDate->diff($duedate)->format('%h') . ' hour' . $this->plural($nowDate->diff($duedate)->format('%h'));
        }
        if($lResArr['flag'] == 1) {
            return $lResArr['datediff'];
        }


    }

    function plural($n) {
        return $n > 1 ? 's' : '';
    }

}
