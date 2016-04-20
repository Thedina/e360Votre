//    public function test()
//    {
//        $this->createUpdaterTables();
//        $this->indexLocalDatabase();
//        $base = $this->serializeLocalDatabase();
//        $delta = file_get_contents('/home/taylorsv/public_html/debug/slco_db_serialize_150626.txt');
//        $base = json_decode($base, true);
//        $delta = json_decode($delta, true);
//        var_dump($this->diffSerializations($base, $delta));
//        $this->syncTables($base, $delta);
//    }



/update
/update/settings
/update/remove

/update/api/request/new     POST
/update/api/request/status  GET
/update/api/request/sync    POST, send request package to other party, both will keep newest package

/update/sqlsync
/update/sqlsync/rebuild
/update/sqlsync/sync        

/update/api/sqlsync/new     POST from CLIENT, method '/sqlsync/diff'
/update/api/sqlsync/diff    MASTER LOCAL POST, diff with request package, update request to method '/request/sync'


C   /update/sqlsync/sync        REQUEST::create('/sqlsync/new'), /request/new @ MASTER
M   /update/api/request/new     REQUEST::store, REQUEST->status('Pending')
M%  /update/cron                NEXT REQUEST with Pending
    ->/update/api/sqlsync/new   SQLSYNC::diffWithRequest, REQUEST->status('Ready')
M%  /update/cron                NEXT REQUEST with Ready, /request/sync @ CLIENT
C   /update/api/request/sync    receives REQUEST->sync(); REQUEST->status('Received')


request
    request_id  bigint
    client_id   int
    lastupdated timestamp
    module      string
    function    string
    status      int
    attachment  bool
    
    client      array
        client_id
        client_auth
        host
        ip
        system

    