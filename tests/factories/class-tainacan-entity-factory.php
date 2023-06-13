<?php

namespace Tainacan\Tests\Factories;

use InvalidArgumentException;

class Entity_Factory {

	/**
	 * 
	 * @var \Tainacan\Entities\Entity
	 */
	private $entity;

	/**
	 * 
	 * @var \Tainacan\Repositories\Repository
	 */
	protected $repository;
	protected $entity_type;
	protected $repository_type;

	/**
	 * Receive a type of the collection as string, an associative array,
	 * a boolean that say if the values need to be validated and inserted in database, and then
	 * create the entity type ordered and return it.
	 *
	 * @param $type
	 * @param array $args
	 * @param bool $is_validated_and_in_db
	 *
	 * @param bool $publish
	 *
	 * @throws InvalidArgumentException
	 *
	 * @return mixed
	 */
	public function create_entity($type, $args = [], $is_validated_and_in_db = false, $publish = false){
		ini_set('display_errors', 1);

		try {
			$type = trim($type);

			if(empty($type)){
				throw new InvalidArgumentException('The type can\'t be empty');
			} elseif(!strrchr($type, '_')){
				$type = ucfirst(strtolower($type));
			} else {
				$type = ucwords(strtolower($type), '_');
			}

			$this->entity_type = "\Tainacan\Entities\\$type";

			$type_size = strlen($type);

			if($type[$type_size-1] == 'y'){
				$type[$type_size-1] = 'i';
				$this->repository_type = "\Tainacan\Repositories\\$type".'es';
			} elseif($type == 'Metadatum'){
				$this->repository_type = "\Tainacan\Repositories\Metadata";
			} elseif($type == 'Metadata_Section'){
				$this->repository_type = "\Tainacan\Repositories\Metadata_Sections";
			}
			else {
				$this->repository_type = "\Tainacan\Repositories\\$type".'s';
			}

			$this->entity     = new $this->entity_type();
			$repo = $this->repository_type;
			$this->repository = $repo::get_instance();
			
			if($publish) {
				$this->entity->set_status('publish');
			}

			if (!empty($args) && $is_validated_and_in_db) {
				foreach ($args as $attribute => $content) {
					$set_ = 'set_' . trim($attribute);
					$this->entity->$set_( $content );
				}

				if ($this->entity->validate()) {
					$this->entity = $this->repository->insert($this->entity);
				} else {
					throw new \ErrorException('The entity wasn\'t validated.' . print_r( $this->entity->get_errors(), true));
				}

			} elseif (!empty($args) && !$is_validated_and_in_db){
				foreach ($args as $attribute => $content) {
					$set_ = 'set_' . trim($attribute);
					$this->entity->$set_( $content );
				}

			} elseif (empty($args) && !$is_validated_and_in_db) {
				try {
					$this->entity->set_name( "$type " . rand( 0, 10000 ) . " for test" );
					$this->entity->set_description( 'It is only for test' );
				} catch (\Exception $exception){
					$this->entity->set_title( "$type " . rand( 0, 10000 ) . " for test" );
					$this->entity->set_description( 'It is only for test' );
				}

			} elseif (empty($args) && $is_validated_and_in_db) {
				try {
					$this->entity->set_name( "$type " . rand( 0, 10000 ) . " for test" );
					$this->entity->set_description( 'It is only for test' );
				} catch (\Exception $exception){
					$this->entity->set_title( "$type " . rand( 0, 10000 ) . " for test" );
					$this->entity->set_description( 'It is only for test' );
				}

				$this->entity->validate();
				$this->entity = $this->repository->insert( $this->entity );
			} else {
				throw new InvalidArgumentException('One or more arguments are invalid.');
			}
		} catch (\Exception $exception){
			echo "\n" . $exception->getMessage() . "\n";
		}
		
		return $this->entity;
	}
}
