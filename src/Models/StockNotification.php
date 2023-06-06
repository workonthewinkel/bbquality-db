<?php

    namespace BbqData\Models;

    use BbqData\Contracts\Model;
    use Illuminate\Database\Eloquent\SoftDeletes;

    class StockNotification extends Model
    {

        use SoftDeletes;

        /**
         * Customers table
         *
         * @var string
         */
        protected $table = 'stock_notifications';

        protected $fillable = [
            'product_id',
            'user_id',
            'email',
            'notified_at'
        ];

        /**
         * Only the id is guarded
         *
         * @var array
         */
        protected $guarded = ['id'];

        /**
         * Return the user connection
         *
         * @return void
         */
        public function user()
        {
            return $this->belongsTo('BbqData\Models\User', 'user_id', 'ID' );    
        }

        /**
         * An order row has a Product
         *
         * @return void
         */
        public function product()
        {
            return $this->belongsTo('BbqData\Models\Product', 'product_id', 'ID' );
        }



    }
