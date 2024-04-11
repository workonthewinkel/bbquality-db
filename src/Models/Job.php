<?php

    namespace BbqData\Models;

    use BbqData\Contracts\Model;
    use BbqData\Models\Casts\Json;

    class Job extends Model
    {
        /**
         * Customers table
         *
         * @var string
         */
        protected $table = 'jobs';

        /**
         * Only the id and order_id are guarded
         *
         * @var array
         */
        protected $fillable = [
            'object',
            'object_id',
            'status',
            'replies',
            'action',
            'run_after'
        ];

        /**
         * Cast the object field as a json
         *
         * @var array
         */
        protected $casts = [
            'object' => Json::class,
        ];


        /**
         * Constant with allowed states
         *
         * @var array
         */
        const STATES = [
            'open' => 0,
            'completed' => 1,
            'error' => 2,
            'retry' => 3,
            'in_progress' => 4,
        ];



        /**
         * Set a completed job.
         *
         * @return void
         */
        public function complete()
        {
            $this->status = self::STATES['completed'];
            $this->save();
        }

        /**
         * Set an errored job
         *
         * @return void
         */
        public function error()
        {
            $this->status = self::STATES['error'];
            $this->save();
        }

        /**
         * Find the first active 
         *
         * @param Array $data
         * @return Job
         */
        public static function findOpen( $data ) 
        {
            $data['status'] = self::STATES['open'];
            return static::where( $data )->first();
        }


        /**
         * Return a specific state in this class
         *
         * @param string $state
         * @return int
         */
        public static function state( $state )
        {
            return self::STATES[ $state ] ?? null;
        }

        /**
         * Create a job if it doesn't exist yet
         *
         * @param Array $data
         * @return Job
         */
        public function createIfNotExists( $data )
        {
            $job = $this->where([
                'object_id' => $data['object_id'] ??  null,
                'action'    => $data['action'],
                'status'    => self::STATES['open']
            ])->first();

            if( is_null( $job ) ){
                $job = new Job();
            }

            $job->fill( $data );
            $job->save();

            return $job->fresh();
        }
    }
