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


    }
