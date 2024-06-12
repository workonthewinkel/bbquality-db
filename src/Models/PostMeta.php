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
		 * Ugh, wordpress
		 */
		protected $primaryKey = 'meta_id';

        /**
         * Customers table
         *
         * @var string
         */
        protected $table = 'postmeta';

		/**
		 * Post meta belongs to a post
		 */
		public function post()
		{
			return $this->belongsTo( Post::class );
		}

	}
