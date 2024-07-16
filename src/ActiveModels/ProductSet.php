<?php

    namespace BbqData\ActiveModels;

    use Carbon\Carbon;
    use BbqData\Contracts\ActiveModel;


    class ProductSet extends ActiveModel{

        /**
         * Data object
         *
         * @var array
         */
        protected $data;

        /**
         * Slug of the endpoint
         */
        protected $slug;

		/**
		 * Email of the user
		 */
		protected $email;

		/**
		 * Cookie of this product set
		 */
		protected $cookie;


        /**
         * Constructor
         */
        public function __construct( string $slug, string $email = '', string $cookie = '' )
        {
            $this->connect_collection();
            $this->slug = $slug;
			$this->email = $email;
            $this->cookie = $cookie;

			$data = $this->get_product_set();
			if( !is_null( $data ) ){
				$this->data = $data;
			}
        }     
        
        /**
         * Return the collection name for this active model
         *
         * @return string
         */
        public static function get_collection_name() 
        {
            return 'product_sets';   
        }


		 /**
         * Create a new cart in Mongo,
         * Send back the ID:
         */
        public static function create( $data ) 
        {
			//set dates:
			$data['created_at'] = Carbon::now()->timestamp;
            $data['updated_at'] = Carbon::now()->timestamp;
			$data['delete_after'] = static::delete_after();

			//save product set:
            $result = static::get_collection( 'product_sets' )->insertOne( $data );
            return (string) $result->getInsertedId();
        }  

        /*====================================*/
        /*          Getters                   */
        /*====================================*/

        /**
         * Return any data in the data-array
         *
         * @return mixed
         */
        public function get( string $key, $default = null )
        {
            if( isset( $this->data[ $key ] ) ){
                return $this->data[ $key ];
            }

            return $default;
        }


		/**
         * Does this set exist?
         */
        public function exists(): bool
        {
            return ( empty( $this->data ) ? false : true );
        }



		/**
         * Fetch the mongo product set:
         */
        public function get_product_set(): ?array 
        {
			$response = [];
            $result = $this->collection->findOne( $this->id() );
            if( !is_null( $result ) ){
				$result = iterator_to_array( $result );
				
				foreach( $result as $key => $value ){

					//get the array value
					if( is_a( $value, 'MongoDB\Model\BSONArray' ) ){
						$response[ $key ] = $value->getArrayCopy();
					}else{
						$response[ $key ] = $value;
					}

				}

                return $response;
            }

            return null;
        }

		/**
		 * Save a product set
		 *
		 * @param array $data
		 * @return void
		 */
		public function save( $data )
		{
			//set the delete_after and updated_at properties:
            $data['delete_after'] = static::delete_after();
            $data['updated_at'] = Carbon::now()->timestamp;

            //set the data, so we can use it later.
            $this->data = $data;

            //and save the record:
            $this->collection->updateOne( $this->id(), ['$set' => $data ]);
		}


		/**
         * Get the delete after date
         *
         * @return int (timestamp)
         */
        public static function delete_after()
        {
            return Carbon::now()->addWeeks( 2 )->timestamp;   
        }



		/**
		 * Returns the array of fields that we use to fetch this record
		 * (Slug and optionally email)
		 */
		public function id(): array
		{
			return [ 'slug' => $this->slug, 'email' => $this->email ];
		}

       
    }
