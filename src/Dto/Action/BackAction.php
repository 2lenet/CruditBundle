<?php

declare(strict_types=1);

namespace Lle\CruditBundle\Dto\Action;

use Lle\CruditBundle\Contracts\BrickConfigInterface;
use Lle\CruditBundle\Contracts\BrickInterface;
use Lle\CruditBundle\Contracts\CrudConfigInterface;
use Lle\CruditBundle\Dto\Path;
use Symfony\Component\HttpFoundation\Request;

class BackAction extends ListAction
{
    public function generate(CrudConfigInterface $crudConfig, Request $request)
    {
        if($request->headers->get('referer')){
            $this->setUrl($request->headers->get('referer'));
        }else{
            $this->path = $crudConfig->getPath();
        }
    }
}
