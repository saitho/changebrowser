<?php
namespace AppBundle\Helper;
use \Doctrine\ORM\EntityManager;
use \Doctrine\ORM\QueryBuilder;
use \Doctrine\ORM\Query;

class ReWatajaxDoctrine {
	/** @var EntityManager $em */
	private $em;
	/** @var QueryBuilder $qb */
	private $qb;
	/** @var Query $query */
	private $query;
	
	private $table;
	private $params;
	
	public function __construct(EntityManager $entityManager, $tableName=null) {
		$this->em = $entityManager;
		$this->qb = new QueryBuilder($this->em);
		$this->query = $this->em->createQuery();
		$this->table = $tableName;
	}
	
	protected $where = null;
	
	protected $options = [];
	protected $headerConfiguration = [];
	function setHeaderConfiguration($headerConfiguration) {
		$this->headerConfiguration = $headerConfiguration;
	}
	function setOptions($options) {
		$this->options = $options;
	}
	function getOptions() {
		return $this->options;
	}
	
	function setWhere($where) {
		$this->where = $where;
	}
	function setTable($table) {
		$this->table = $table;
	}
	function setParams($params) {
		$this->params = $params;
	}
	
	function findResults() {
		$this->query->setParameters($this->params);
		$this->qb->select('a')->from($this->table, 'a');
		if(!empty($this->where)) {
			$this->qb->where($this->where);
		}
		
		if(!empty($this->options['sortedBy']) && !empty($this->options['sortMode'])) {
			$this->qb->orderBy('a.'.$this->options['sortedBy'], $this->options['sortMode']);
		}
		if(!empty($this->options['search'])) {
			$orX = $this->qb->expr()->orX();
			foreach($this->headerConfiguration AS $k => $v) {
				if(!empty($v['virtual'])) {
					continue;
				}
				if(!empty($v['searchFieldName'])) {
					$orX2 = $this->qb->expr()->orX();
					for($i=0; !empty($v['searchFieldName'][$i]); $i++) {
						$andX = $this->qb->expr()->andX();
						$andX->add('a.'.$v['searchFieldName'][$i].' LIKE :search');
						for($i2=0; $i2 < $i; $i2++) {
							$andX->add('a.'.$v['searchFieldName'][$i2].' IS NULL');
						}
						$orX2->add($andX);
					}
					$orX->add($orX2);
				}else{
					$orX->add('a.'.$k.' LIKE :search');
				}
			}
			$this->qb->andWhere($orX);
			$this->query->setParameter('search', '%'.$this->options['search'].'%');
		}
		if(!empty($this->options['filter'])) {
			foreach ($this->options['filter'] AS $filter) {
				foreach($filter['filterOptions'] AS $filterOption) {
					$filterKey = $filterOption['filterKey'];
					switch($filter['filterType']) {
						case 'datetime':
							// 0 = start; 1 = end
							if(count($filterOption['filterValues']) >= 1) {
								$startDate = new \DateTime($filterOption['filterValues'][0]);
								$startDate->setTime(0, 0, 0);
								$this->qb->andWhere('a.'.$filterKey . ' >= :' . $filterKey . '_startDate');
								$this->query->setParameter($filterKey . '_startDate', $startDate);
							}
							if(count($filterOption['filterValues']) >= 2) {
								$endDate = new \DateTime($filterOption['filterValues'][1]);
								$endDate->setTime(23, 59, 59);
								$this->qb->andWhere('a.'.$filterKey.' <= :'.$filterKey.'_endDate');
								$this->query->setParameter($filterKey.'_endDate', $endDate);
							}
							break;
					}
				}
			}
		}
		$this->query->setDQL($this->qb->getDQL());
		
		$this->options['total_results'] = count($this->query->getResult());
		if(!empty($this->options['per_page'])) {
			$this->query->setFirstResult( $this->options['current_page']*$this->options['per_page']-$this->options['per_page'] )
				->setMaxResults( $this->options['per_page'] );
		}
		
		$result = $this->query->getResult();
		if(!empty($this->options['per_page'])) {
			$this->options['total_pages'] = ceil($this->options['total_results']/$this->options['per_page']);
		}
		
		return $result;
	}
}