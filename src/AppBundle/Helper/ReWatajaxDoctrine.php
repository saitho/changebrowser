<?php
namespace AppBundle\Helper;

class ReWatajaxDoctrine {
	/** @var \Doctrine\ORM\EntityManager $em */
	private $em;
	/** @var \Doctrine\ORM\QueryBuilder $qb */
	private $qb;
	/** @var \Doctrine\ORM\Query $query */
	private $query;
	
	private $table;
	
	public function __construct(\Doctrine\ORM\EntityManager $entityManager, $tableName=null) {
		$this->em = $entityManager;
		$this->qb = new \Doctrine\ORM\QueryBuilder($this->em);
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
		$this->query->setParameters($params);
	}
	
	function findResults() {
		$this->qb->select('a')->from($this->table, 'a');
		if(!empty($this->where)) {
			$this->qb->where($this->where);
		}
		
		$this->qb->orderBy('a.'.$this->options['sortedBy'], $this->options['sortMode']);
		if(!empty($this->options['search'])) {
			$orX = $this->qb->expr()->orX();
			foreach($this->headerConfiguration AS $k => $v) {
				if(!empty($v['virtual'])) {
					continue;
				}
				$orX->add('a.'.$k.' LIKE :search');
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