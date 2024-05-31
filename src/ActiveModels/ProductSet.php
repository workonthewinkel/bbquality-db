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
         * Constructor
         */
        public function __construct( string $slug, string $email = '' )
        {
            $this->connect_collection();
            $this->slug = $slug;
			$this->email = $email;

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
         * Returns wether or not a cart is new or old:
         *
         * @return boolean
         */
        public function is_old(): bool
        {
            $updated_at = $this->get( 'updated_at', null );
			//a cart is considered old if it's been over 30 minutes:
            if( !is_null( $updated_at ) && Carbon::createFromTimestamp( $updated_at )->diffInMinutes( Carbon::now() ) > 30 ){
                return true;
            }
            return false;
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
            $result = $this->collection->findOne( $this->id() );
            if( !is_null( $result ) ){
                return iterator_to_array( $result );
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
