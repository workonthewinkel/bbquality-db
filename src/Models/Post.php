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
         * Return the related posts as related 
         *
         * @return void
         */
        public function get_related_posts()
        {
            $relation_ids = $this->relations->pluck('related_post_id')->toArray();
            return $this->whereIn('ID', $relation_ids )->get();
        }

    }
