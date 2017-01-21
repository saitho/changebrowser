<?php
namespace AppBundle\Helper;

class ReWatajaxDoctrine {
	/** @var \Doctrine\ORM\EntityManager $em */
	private $em;
	/** @var \Doctrine\ORM\QueryBuilder $qb */
	private $qb;
	
	private $table;
	
	public function __construct(\Doctrine\ORM\EntityManager $entityManager, $tableName=null) {
		$this->em = $entityManager;
		$this->qb = new \Doctrine\ORM\QueryBuilder($this->em);
		$this->table = $tableName;
	}
	
	protected $where = null;
	protected $params = [];
	
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
		$query = $this->em->createQuery();
		$query->setParameters($this->params);
		
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
			$query->setParameter('search', '%'.$this->options['search'].'%');
		}
		$query->setDQL($this->qb->getDQL());
		
		$this->options['total_results'] = count($query->getResult());
		if(!empty($this->options['per_page'])) {
			$query->setFirstResult( $this->options['current_page']*$this->options['per_page']-$this->options['per_page'] )
				->setMaxResults( $this->options['per_page'] );
		}
		
		$result = $query->getResult();
		if(!empty($this->options['per_page'])) {
			$this->options['total_pages'] = ceil($this->options['total_results']/$this->options['per_page']);
		}
		
		return $result;
	}
}