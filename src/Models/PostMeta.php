<?php

    namespace BbqData\Models;


    use BbqData\Contracts\Model;

    class PostMeta extends Model
    {
        
        /**
         * Don't keep timestamps
         *
         * @var boolean
         */
        public $timestamps = false;

		/**
		 * The primary key for the model.
		 *
		 * @var string
		 */
		protected $primaryKey = 'meta_id';
		
        /**
         * Customers table
         *
         * @var string
         */
        protected $table = 'postmeta';

        /**
         * Only the id is guarded
         *
         * @var array
         */
        protected $guarded = ['id'];


    }
