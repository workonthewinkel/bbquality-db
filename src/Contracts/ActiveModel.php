<?php

    namespace BbqData\Contracts;

    use MongoDB\Client;
    use MongoDB\BSON\ObjectId;

    abstract class ActiveModel{

        /**
         * MongoDB Collection
         *
         * @var MongoCollection
         */
        protected $collection;

        
        /**
         * Save a cart in MongoDB
         *
         * @param int $object_id
         * @param array $data
         * @return array
         */
        public function save_data( $object_id, $data )
        {            
            $cart = $this->fetch_data( $object_id );
            if( !is_null( $cart ) ){
                $this->collection->updateOne([ '_id' => new ObjectId( $object_id ) ], ['$set' => $data ]);
            }
        }

        /**
         * Fetch the mongo cart:
         *
         * @param int $object_id
         * @return array
         */
        public function fetch_data( $object_id )
        {
            $result = $this->collection->findOne(['_id' => new ObjectId( $object_id ) ]);
            if( !is_null( $result ) ){
                return iterator_to_array( $result );
            }

            return null;
        }


        /**
         * Find a single object
         *
         * @param array $query
         * @return array
         */
        public static function find( $query )
        {
            $result = self::get_collection()->findOne( $query );
            if( !is_null( $result ) ){
                return iterator_to_array( $result );
            }

            return null;
        }


        /**
         * Query a collection to receive an array of results    
         *
         * @param array $query
         * @return array
         */
        public function query( $query )
        {
            $result = $this->collection->find( $query );
            if( !is_null( $result ) ){
                return iterator_to_array( $result );
            }

            return [];
        }


        /**
         * Delete the cart
         *
         * @param int $order_id
         * @return bool
         */
        public static function delete( $query )
        {
            $result = self::get_collection()->deleteOne( $query );
            $count = $result->getDeletedCount();
            return ( $count === 1 );
        }


        /**
         * Check if this is a valid id
         *
         * @param int $id
         * @return boolean
         */
        public function valid_id( $id )
        {
            try{
                
                $id = new ObjectId( $id );
                return true;

            }catch( \Throwable $e ){
                return false;
            }
        }

        /**
         * Set the mongoDB connection
         *
         * @return void
         */
        public function connect_collection( $name = null )
        {
            //set the collection:
            $this->collection = self::get_collection( $name );

            //return this instance so we can chain commands:
            return $this;
        }

        /**
         * Get the mongo connection, statically
         *
         * @return MongoCollection
         */
        public static function get_collection( $name = null ) 
        {
            $db = env( 'mongo_db' );
            $client = new Client( env('mongo_url' ) );

            if( is_null( $name ) ){
                $name =  static::get_collection_name();
            }

            //find the collection
            $collection = $client->selectCollection( $db, $name );
        
            //return it:
            return $collection;
        }


        /**
         * Return the default collection name
         *
         * @return void
         */
        public static function get_collection_name() 
        {
            return 'items';    
        }


        
    }
