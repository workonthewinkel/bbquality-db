<?php

    namespace BbqData\Models;


    use BbqData\Contracts\Model;

    class Post extends Model
    {
        
        /**
         * Don't keep timestamps
         *
         * @var boolean
         */
        public $timestamps = false;

        /**
         * Customers table
         *
         * @var string
         */
        protected $table = 'posts';

        /**
         * Only the id is guarded
         *
         * @var array
         */
        protected $guarded = ['id'];


        public function relations()
        {
            return $this->hasMany( Relation::class, 'post_id', 'ID' );
        }


        /**
         * Returns the upsells or bought-together products for this
         *
         * @return void
         */
        public function get_upsells()
        {
            $upsells = $this->relations()->upsells()->get();
            if( $upsells->isEmpty() ){
                $upsells = $this->relations()->boughtTogether()->get();
            }

            return $upsells;
        }

        /**
         * Returns wether or not this 
         *
         * @return boolean
         */
        public function has_upsells()
        {
            $upsells = $this->relations()->upsells()->get();
            if( $upsells->isEmpty() ){
                return false;
            }

            return true;
        }

        /**
         * Return the related posts as related 
         *
         * @return void
         */
        public function get_related_posts()
        {
            $relations = $this->relations()->related()->get();
            if( !is_null( $relations ) ){
                $relation_ids = $relations->pluck('related_post_id')->toArray();
                return $this->whereIn('ID', $relation_ids )->get();
            }

            return \collect([]);
        }


        /**
         * Return the related posts as a WP_Query
         *
         * @return WP_Query
         */
        public function get_related_query()
        {
            if( !class_exists( 'WP_Query' ) ){
                return null;
            }

            $relations = $this->relations()->get();
            if( !is_null( $relations ) ){
                $relation_ids = $relations->pluck('related_post_id')->toArray();
                return ( new \WP_Query( [ 'post__in' => $relation_ids, 'post_type' => 'any' ] ) );
            }

            return null;
        }

    }
