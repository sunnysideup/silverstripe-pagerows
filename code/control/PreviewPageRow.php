<?php


class PreviewPageRow extends ContentController
{
    private static $url_segment = 'preview-page-row';

    private static $allowed_actions = [
        'preview' => 'ADMIN',
        'view' => true
    ];

    private $pageRows = null;

    public function preview($request)
    {
        return $notFoundResponse = $this->processRequest($request, false);
    }

    /**
     *
     * @param string | null $action
     * @return string
     */
    public function Link($action = null)
    {
        $link = '/'.$this->Config()->get('url_segment').'/';
        if ($action) {
            $link .= $action . '/';
        }

        return $link;
    }

    public function view($request)
    {
        return $this->processRequest($request, true);
    }

    protected function processRequest($request, $readyForPublic)
    {
        $id = $request->param('ID');
        $pageRowClassNames = ClassInfo::subclassesFor('PageRow');
        $listOfClasses = [];
        foreach ($pageRowClassNames as $key => $class) {
            $listOfClasses[strtolower($class)] = $class;
        }
        $errorResponse = null;
        PageRow::set_current_page_object(Page::get()->first());
        if ($id == 'all') {
            $this->pageRows = PageRow::get();
        } elseif ($id == 'oneofeach') {
            $tempList = PageRow::get()->sort('RAND()');
            $oneIDPerClassNameArray = [];
            foreach ($tempList as $obj) {
                $oneIDPerClassNameArray[$obj->ClassName] = $obj->ID;
            }
            $this->pageRows = PageRow::get()->filter(['ID' => $oneIDPerClassNameArray]);
        } elseif (isset($listOfClasses[$id])) {
            $this->pageRows = PageRow::get()->filter(['ClassName' => $listOfClasses[$id]]);
        } else {
            $id = intval($id);
            $this->pageRows = PageRow::get()->filter(['ID' =>$id]);
        }
        if ($readyForPublic) {
            $this->pageRows = $this->pageRows->filter(['ReadyForPublication' => 1]);
        }
        $this->pageRows = $this->pageRows->exclude(['ClassName' => 'DownloadBlock']);
        if (! $this->pageRows->count()) {
            $errorResponse = $this->httpError(404, 'The requested page block could not be found.');
        }
        if ($errorResponse) {
            return $errorResponse;
        } else {
            return $this->renderWith('PreviewPageRow');
        }
    }


    public function PageRowsReadyForPublication()
    {
        return $this->pageRows;
    }
}
