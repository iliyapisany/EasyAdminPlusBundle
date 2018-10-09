<?php

namespace Lle\EasyAdminPlusBundle\Filter\FilterType;

use DateTime;

use Symfony\Component\HttpFoundation\Request;

/**
 * DateFilterType
 */
class PeriodeFilterType extends AbstractFilterType
{

    private $choices;
    private $requestChoice;

    public function __construct($columnName, $label, $config = array(), $alias = 'entity')
    {
        parent::__construct($columnName, $label, $config, $alias);
        $this->choices = (isset($config['choices']))? $config['choices']:null;
        $this->format = (isset($config['format']))? $config['format']:'d/m/Y';
    }

    /**
     * @param array  $data     The data
     * @param string $uniqueId The unique identifier
     */
    public function apply($queryBuilder)
    {
        if (isset($data['value'])) {
            $qb = $this->queryBuilder;
            $from = $to = null;
            if(isset($data['value']['from']) && $data['value']['from']) $from = DateTime::createFromFormat($this->format, $data['value']['from'])->format('Y-m-d');
            if(isset($data['value']['to']) && $data['value']['to']) $to = DateTime::createFromFormat($this->format, $data['value']['to'])->format('Y-m-d');
            $c = $alias . $col;
            if(isset($data['value']['to']) and $data['value']['to']){
                $qb->andWhere($c.' <= :var_to_'.$uniqueId);
                $this->queryBuilder->setParameter('var_to_' . $uniqueId, $to);
            }
            if(isset($data['value']['from']) and $data['value']['from']){
                $qb->andWhere($c. ' >= :var_from_' . $uniqueId);
                $this->queryBuilder->setParameter('var_from_' . $uniqueId, $from);
            }
        }
    }

    public function getChoices(){
        $return = array();
        if(!$this->choices) return $return;
        foreach($this->choices as $choice){
            $from = (isset($choice['from']))? $choice['from']:'getFrom';
            $to = (isset($choice['to']))? $choice['to']:'getTo';
            $m = (isset($choice['method']))? $choice['method']:'findAll';
            if($from and $to and $m and isset($choice['table'])){
                list($bundle,$label) = explode(':',$choice['table']);
                $list = strtolower($label);
                $label = (isset($choice['label']))? $choice['label']:$label;
                $elms = $this->getRepository($choice['table'])->$m();
                $return[$label] = array('list'=>$list,'items'=>array());
                foreach($elms as $elm){
                    $f = $elm->$from();
                    $t = $elm->$to();
                    if($f and $t){
                        $return[$label]['items'][] = array(
                            'from'=>$elm->$from()->format($this->format),
                            'to'=>$elm->$to()->format($this->format),
                            'id'=>$elm->getId(),
                            'label'=>$elm->__toString());
                    }
                }
            }else{
                throw new \Exception('PeriodFilter: Element dans choices invalide choices[elm[from,to,table,method,label]]');
            }
        }
        return $return;

    }

    public function isSelected($list,$elm){
        if(is_array($this->requestChoice) and isset($this->requestChoice[$list])){
            return ($elm['id'] == $this->requestChoice[$list]);
        }else{
            return false;
        }
    }

}