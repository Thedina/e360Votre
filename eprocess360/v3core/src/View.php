<?php
/**
 * Created by PhpStorm.
 * User: Kira
 * Date: 8/18/2015
 * Time: 5:17 PM
 */

namespace eprocess360\v3core;
use eprocess360\v3core\Keydict\Entry;
use eprocess360\v3core\Keydict\StorageStrategy\SingleColumn;
use eprocess360\v3core\Keydict\Table;


/**
 * Class View
 * @package eprocess360\v3core
 * @deprecated
 */
class View
{
    protected $keydict;
    protected $template;
    protected $singleTemplate;
    protected $offset = 0;

    /**
     * @param $id
     * @param Table $keydict
     * @param $template
     * @param $singleTemplate
     */
    public function __construct($id, Table $keydict, $template, $singleTemplate)
    {
        $this->id = $id;
        $this->keydict = $keydict;
        $this->keydict->setId($id);
        $this->template = $template;
        $this->singleTemplate = $singleTemplate;
    }

    /**
     * @return \Generator
     */
    public function streamOut()
    {
        foreach ($this->keydict->fetch() as $out) {
            yield $out;
        }
    }

    /**
     * @return string
     */
    public function render()
    {
        global $pool, $twig;
        $pool->add($this, 'View');
        return $twig->render($this->template, $pool->asArray());
    }

    public function getNext()
    {
        return $this->keydict->getLastField();
    }

    public function getID()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function json()
    {
        $buffer = [];
        /**
         * @var Entry $row
         */
        foreach ($this->streamOut() as $row) {
            $row->setStorageStrategy(new SingleColumn());
            $rowData = json_decode(current($row->sleep()));
            // need to handle flags
            $buffer[] = $rowData;
        }
        return json_encode(['results'=>$buffer,'api'=>'', 'sql'=>$this->keydict->getLastSql(), 'more'=>$this->getMore(), 'less'=>$this->getLess(), 'current'=>$this->getCurrent()]);
    }

    /**
     * @return mixed|string
     */
    public function getSingleTemplate()
    {
        global $twig_loader;
        $raw = $twig_loader->getSource($this->singleTemplate);
        /* This performs the substitution replacements */
        $raw = preg_replace("/{% .*? %}{# (.*?) #}/", "$1", $raw);

        /* This does automatic substitution for variables */
        $raw = preg_replace_callback("/{{ (.*?) }}/", function ($match) {
            $match = $match[1];
            if (substr($match,0,6) == 'SysVar') {
                // SysVar, we'll just insert the value
                global $pool;
                return $pool->SysVar->get(substr($match,strpos($match,'(')+2,-2));
            } else {
                // We will expect the object in the JSON
                $match = substr_replace($match, 'this', 0, strpos($match,'.'));
                $match = substr($match,0,strpos($match, '.get()'));
                return "<%{$match}%>";
            }
        }, $raw);
        return $raw;
    }

    public function getMore()
    {
        return $this->keydict->getMore();
    }

    public function getLess()
    {
        return $this->keydict->getLess();
    }

    public function getCurrent()
    {
        return $this->keydict->getCurrent();
    }

    public function getLastPage()
    {
        return $this->keydict->getLastPage();
    }

}