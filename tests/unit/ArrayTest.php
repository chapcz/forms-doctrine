<?php

class ArrayTest extends \PHPUnit_Framework_TestCase {

	/** @var \WebChemistry\Forms\Doctrine */
	protected $helper;

	protected function setUp() {
		$config = new \Kdyby\Doctrine\Configuration();
		$mapping = new \Doctrine\ORM\Mapping\Driver\AnnotationDriver(new \Doctrine\Common\Annotations\AnnotationReader(), [__DIR__ . '/entitites']);
		$config->setMetadataDriverImpl($mapping);
		$config->setProxyDir(__DIR__ . '/proxy');
		$config->setProxyNamespace('Tests\_ProxyTests');
		\Doctrine\Common\Annotations\AnnotationRegistry::registerFile(__DIR__ . '/../../vendor/doctrine/orm/lib/Doctrine/ORM/Mapping/Driver/DoctrineAnnotations.php');
		$event = new \Doctrine\Common\EventManager();
		$conn = new \Kdyby\Doctrine\Connection(array(
			'dbname' => 'test',
			'user' => 'travis',
			'password' => '',
			'host' => 'localhost',
			'driver' => 'pdo_mysql',
		), new \Doctrine\DBAL\Driver\PDOMySql\Driver(), $config, $event);
		$em = \Kdyby\Doctrine\EntityManager::create($conn, $config, $event);
		$this->helper = new \WebChemistry\Forms\Doctrine($em);
	}

	private function fillEntity() {
		$entity = new \Entity\User();
		$entity->id = 1;
		$entity->name = 'John';
		$entity->password = 'myPassword';
		$entity->count = 5;

		$role = new \Entity\Role();
		$role->id = 5;
		$role->name = 'Owner';
		//$role->addUser($entity);
		$entity->role = $role;

		$history = new \Entity\History;
		$history->name = 'History';
		//$history->user = $entity;
		$entity->history = $history;

		$cart = new \Entity\Cart();
		$cart->name = 'Cart 1';
		$entity->addCart($cart);
		//$cart->addUser($entity);
		//$entity->cart[] = $cart;

		$cart = new \Entity\Cart();
		$cart->id = 2;
		$cart->name = 'Cart 2';
		//$cart->addUser($entity);
		$entity->addCart($cart);
		//$entity->cart[] = $cart;

		return $entity;
	}

	private function fillArray() {
		return array(
			'id' => 1,
			'name' => 'John',
			'password' => 'myPassword',
			'registration' => NULL,
			'count' => 5,
			'role' => array(
				'id' => 5,
				'name' => 'Owner'
			),
			'history' => array(
				'id' => NULL,
				'name' => 'History'
			),
			'cart' => array(
				0 => array(
					'id' => NULL,
					'name' => 'Cart 1'
				),
				1 => array(
					'id' => 2,
					'name' => 'Cart 2'
				)
			),
			'voidClass' => NULL
		);
	}

	public function checkObject($expected, $actual) {
		foreach (array('id', 'name', 'voidClass', 'registration', 'password', 'count') as $row) {
			$this->assertSame($expected->$row, $actual->$row, 'Base item ' . $row);
		}

		$exp = $expected->role;
		$act = $actual->role;

		if ($exp === NULL) {
			$this->assertNull($act, 'Role is not NULL.');
		} else {
			foreach (array('id', 'name', 'users') as $row) {
				$this->assertSame($exp->$row, $act->$row, 'Role item ' . $row);
			}
		}

		$exp = $expected->history;
		$act = $actual->history;

		if ($exp === NULL) {
			$this->assertNull($act, 'History is not NULL.');
		} else {
			foreach (array('id', 'name', 'user') as $row) {
				$this->assertSame($exp->$row, $act->$row, 'History item ' . $row);
			}
		}

		$exp = $expected->cart;
		$act = $actual->cart;

		if (!$exp) {
			$this->assertEmpty($act, 'Cart is not empty.');
		} else {
			foreach ($expected->cart as $index => $row) {
				foreach (array('name', 'id', 'users') as $column) {
					$this->assertSame($row->$column, $act[$index]->$column, 'Cart item ' . $column);
				}
			}
		}
	}

	public function testBase() {
		$settings = new \WebChemistry\Forms\Doctrine\Settings();

		$this->checkObject($this->fillEntity(), $this->helper->toEntity('Entity\User', $this->fillArray()));
	}

	public function testItems() {
		$entity = $this->fillEntity();

		$entity->password = NULL;
		$entity->role->name = NULL;
		$entity->count = NULL;
		$entity->history = NULL;

		$settings = new \WebChemistry\Forms\Doctrine\Settings();
		$settings->setAllowedItems(array('id', 'name', 'registration', 'role' => array('id'), 'cart' => '*'));

		$this->checkObject($entity, $this->helper->toEntity('Entity\User', $this->fillArray(), $settings));
	}

	public function testManyToMany() {
		$entity = $this->fillEntity();

		$entity->password = NULL;
		$entity->role->name = NULL;
		$entity->count = NULL;
		$entity->history = NULL;
		$entity->clearCart();

		$settings = new \WebChemistry\Forms\Doctrine\Settings();
		$settings->setAllowedItems(array('id', 'name', 'registration', 'role' => array('id')));

		$this->checkObject($entity, $this->helper->toEntity('Entity\User', $this->fillArray(), $settings));
	}
}
