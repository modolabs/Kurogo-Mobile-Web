<?php
$docRoot = getenv("DOCUMENT_ROOT");

require_once $docRoot . "/mobi-config/mobi_lib_constants.php";
require_once LIBDIR . "ShuttleSchedule.php";

$schedule = new ShuttleSchedule();

$schedule
     ->route("Cambridge All", "saferidecamball", "SafeRide")
     ->summary("Runs every evening on holidays and summer")
     ->perHour(1)
     ->stops(
        st("84Mass"                       ,"mass84_d"  , "frcamp", '00'),
	st("McCrmck"                      ,"mccrmk"    , "frcamp", '01'),
	st("Burtn"                        ,"burtho"    , "frcamp", '02'),
	st("NewHse"                       ,"newho"     , "frcamp", '03'),
        st("Tang"                         ,"tangwest"  , "frcamp", '04'),
        st("Simmns"                       ,"simmhl"    , "frcamp", '06'),
        st("WW15"                         ,"ww15"      , "frcamp", '08'),
        st("Brookln@Chest"                ,"brookchest", "frcamp", '09'),
        st("Putnm@Mag"                    ,"putmag"    , "frcamp", '10'),
        st("Rivr@Fairmt"                  ,"rivfair"   , "frcamp", '12'),
        st("Rvr@Uptn"                     ,"rivpleas"  , "frcamp", '13'),
        st("Rvr@Frnkln"                   ,"rivfrank"  , "frcamp", '14'),
        st("Sid@Green"                    ,"sydgreen"  , "tocamp", '16'),
        st("SidPac"                       ,"paci70"    , "tocamp", '18'),
        st("Warehse"                      ,"whou"      , "tocamp", '19'),
        st("Edgrtn"                       ,"edge"      , "tocamp", '20'),
        st("84Mass "                      ,"mass84"    , "frcamp", '30'),
        st("Edgrtn "                      ,"nw10"      , "frcamp", '33'),
        st("Warehse "                     ,"nw30"      , "frcamp", '34'),
        st("SidPac "                      ,"nw86"      , "frcamp", '35'),
        st("RandmHll"                     ,"randhl"    , "tocamp", '36'),
        st("Main@Windsr"                  ,"mainwinds" , "tocamp", '39'),
        st("Prtlnd@Hmpshr"                ,"porthamp"  , "tocamp", '40'),
        st("638Camb"                      ,"camb638"   , "tocamp", '43'),
        st("Camb@5th"                     ,"camb5th"   , "tocamp", '44'),
        st("6th@Charles"                  ,"6thcharl"  , "tocamp", '45'),
        st("EastLot"                      ,"elotmain"  , "tocamp", '47'),
        st("Bld66"                        ,"amesbld66" , "tocamp", '48'),
        st("MITMed"                       ,"mitmed"    , "tocamp", '50'),
        st("KendallT"                     ,"kendsq"    , "tocamp", '52'),
        st("E40"                          ,"wadse40"   , "tocamp", '53'),
        st("77Mass"                       ,"mass77"    , "tocamp", '56'))
     ->addHours("Thu-Sat", hours("18 19 20 21 22 23 0 1 2")) 
     ->addHours("Sun-Wed", hours("18 19 20 21 22 23 0 1"));

$schedule
     ->route("Boston All", "saferidebostonall", "SafeRide")
     ->summary("Runs every evening on holidays and summer")
     ->perHour(1)
     ->stops(
	st("84MassAv"                     ,"mass84_d"   , "comm487", '00'),
        st("MassAv@Beacon"                ,"massbeac"   , "comm487", '02'),
        st("NewburyT"                     ,"massnewb"   , "comm487", '04'),
        st("487CommAv"                    ,"comm487"    ,  "mass84", '06'),
        st("478CommAv"                    ,"comm478"    ,  "mass84", '09'),
        st("MassAv@Beacon "               ,"beacmass"   ,  "mass84", '12'),
        st("77MassAv"                     ,"mass77"     ,  "mass84", '14'),
	st("84MassAv "                    ,"mass84"     ,  "manc58", '15'),
        st("MassAv@Beacon  "              ,"massbeac_b" ,  "manc58", '18'),
        st("528Beacon"                    ,"beac528"    ,  "manc58", '22'),
        st("487CommAv "                   ,"487comm"    ,  "manc58", '28'),
        st("111Baystate"                  ,"bays111"    ,  "manc58", '37'),
        st("155Baystate"                  ,"bays155"    ,  "manc58", '39'),
        st("58Manchester(ZBT)"            ,"manc58"     ,  "mass84", '43'),
        st("259StPaul(ET)"                ,"stpaul259"  ,  "mass84", '46'),
        st("MassAv@Beacon   "             ,"beacmass_a" ,  "mass84", '58'))
     ->addHours("Thu-Sat", hours("18 19 20 21 22 23 0 1 2")) 
     ->addHours("Sun-Wed", hours("18 19 20 21 22 23 0 1"));

$schedule
     ->route("Cambridge East", "saferidecambeast", "SafeRide")
     ->summary("Runs every evening, all year round")
     ->perHour(2)
     ->stops(
	st("84MassAve"           ,"mass84_d" , "frcamp", '00'),
        st("Edgerton"            ,"nw10"     , "frcamp", '03'),
        st("Warehouse"           ,"nw30"     , "frcamp", '04'),
        st("70Pacific"           ,"nw86"     , "frcamp", '05'),
        st("RandomHall"          ,"nw61"     , "frcamp", '06'),
        st("Main@Windsor"        ,"mainwinds", "frcamp", '09'),
        st("Portland@Hampshire"  ,"porthamp" , "frcamp", '10'),
        st("638CambridgeSt"      ,"camb638"  , "frcamp", '13'),
        st("Cambridge@Fifth"     ,"camb5th"  , "frcamp", '14'),
        st("Sixth@CharlesSt"     ,"6thcharl" , "tocamp", '15'),
        st("EastLot"             ,"elotmain" , "tocamp", '17'),
        st("Bldg66"              ,"amesbld66", "tocamp", '18'),
        st("MIT Medical"         ,"mitmed"   , "tocamp", '20'),
        st("KendallT"            ,"kendsq"   , "tocamp", '22'),
        st("E40"                 ,"wadse40"  , "tocamp", '23'),
        st("77MassAve"           ,"mass77"   , "tocamp", '26'))    
     ->addHours("Thu-Sat",
       hours("18-22")->append(delay(5, "23 0 1 2 3:1"))
     ) 
     ->addHours("Sun-Wed",
       hours("18-21")->append(delay(5, "22 23 0 1 2:1")) 
     );

$schedule
     ->route("Cambridge West", "saferidecambwest", "SafeRide")
     ->summary("Runs every evening, all year round")
     ->perHour(2)
     ->stops(
	st("84MassAve"              ,"mass84_d"  , "frcamp", '00'),
        st("McCormick"              ,"mccrmk"    , "frcamp", '01'),
        st("Burton"                 ,"burtho"    , "frcamp", '02'),
        st("NewHouse"               ,"newho"     , "frcamp", '03'),
        st("Tang/Westgate"          ,"tangwest"  , "frcamp", '04'),
        st("Simmons"                ,"simmhl"    , "frcamp", '06'),
        st("WW15(Request)"          , NULL       ,  NULL   , '07'),
        st("Brookline@Chestnut"     ,"brookchest", "frcamp", '09'),
        st("Putnam@Magazine"        ,"putmag"    , "frcamp", '10'),
        st("River@Fairmont"         ,"rivfair"   , "frcamp", '12'),
        st("River@Upton"            ,"rivpleas"  , "frcamp", '13'),
        st("River@Franklin"         ,"rivfrank"  , "tocamp", '14'),
        st("Sydney@Green"           ,"sydgreen"  , "tocamp", '16'),
        st("70Pacific"              ,"paci70"    , "tocamp", '18'),
        st("Warehouse"              ,"whou"      , "tocamp", '19'),
        st("Edgerton"               ,"edge"      , "tocamp", '20'))    
     ->addHours("Thu-Sat",
       hours("18-22")->append(delay(5, "23 0 1 2 3:1"))
     ) 
     ->addHours("Sun-Wed",
       hours("18-21")->append(delay(5, "22 23 0 1 2:1")) 
     );

$schedule
     ->route("Boston West", "saferidebostonw", "SafeRide")
     ->summary("Runs every evening, all year round")
     ->perHour(2)
     ->stops(
	st("84MassAve"                     ,"mass84_d" , "boston", '15'),
        st("MassAve@Beacon"                ,"massbeac" , "boston", '18'),
        st("528BeaconSt"                   ,"beac528"  , "boston", '19'),
        st("487CommAve"                    ,"comm487"  , "boston", '21'),
        st("64Baystate"                    ,"bays64"   , "boston", '23'),
        st("111Baystate"                   ,"bays111"  , "boston", '24'),
        st("155Baystate"                   ,"bays155"  , "boston", '25'),
        st("259StPaulSt(ET)"               ,"stpaul259", "boston", '32'),
        st("58Manchester(ZBT)"             ,"manc58"   , "mass84", '34'),
	st("550MemorialDr"                 ,"memo550"  , "mass84", '40'),
        st("SimmonsHall"                   ,"simmhl"   , "mass84", '41'))
     ->addHours("Thu-Sat",
       hours("18-22")->append(delay(5, "23 0 1 2 3:1"))
     ) 
     ->addHours("Sun-Wed",
       hours("18-21")->append(delay(5, "22 23 0 1 2:1")) 
     );


$schedule
     ->route("Boston East", "saferidebostone", "SafeRide")
     ->summary("Runs every evening, all year round")
     ->perHour(2)
     ->stops(
	st("84MassAve"                 ,"mass84_d" ,"boston", '00'),
        st("MassAve/Beacon"            ,"massbeac" ,"boston", '02'),
        st("478CommAve"                ,"comm478"  ,"boston", '04'),
        st("Vanderbilt(Request)"       , NULL      , NULL   , '06'),
        st("28Fenway"                  ,"fenw28"   ,"boston", '10'),
        st("PrudentialCenter"          ,"prud"     ,"boston", '12'),
        st("229CommAve"                ,"comm229"  ,"boston", '15'),
        st("253CommAve"                ,"comm253"  ,"mass84", '16'),
        st("32Hereford"                ,"here32"   ,"mass84", '17'),
        st("450Beacon"                 ,"beac450"  ,"mass84", '18'),
        st("Beacon@MassAve"            ,"beacmass" ,"mass84", '19'))
     ->addHours("Thu-Sat",
	 hours("18-22")->append(delay(5, "23 0 1 2 3:1"))
     ) 
     ->addHours("Sun-Wed",
	 hours("18-21")->append(delay(5, "22 23 0 1 2:1")) 
     );

$schedule
     ->route("Tech Shuttle", "tech")
     ->summary("Runs weekdays 7AM-6PM, all year round")
     ->except_holidays()
     ->perHour(3)
     ->stops(
	st("KendallSqT"                    ,"kendsq_d", "wcamp" , '15'),
        st("Amherst/Wadsworth"             ,"amhewads", "wcamp" , '17'),
        st("MediaLab"                      ,"medilb"  , "wcamp" , '18'),
        st("Bldg39"                        ,"build39" , "wcamp" , '20'),
        st("84MassAve"                     ,"mass84"  , "wcamp" , '22'),
        st("BurtonHouse"                   ,"burtho"  , "wcamp" , '24'),
        st("Tang/Westgate"                 ,"tangwest", "wcamp" , '25'),
        st("W92@AmesburySt"                ,"w92ames",  "kendsq", '26'),
        st("SimmonsHall"                   ,"simmhl"  , "kendsq", '27'),
        st("Vassar/MassAve"                ,"vassmass", "kendsq", '29'),
        st("Stata"                         ,"statct"  , "kendsq", '30'))
     ->addHours("Mon-Fri",
       hours("7-18"),
       delay(30, "7-9"),
       delay(-10, "16-17")
     ); 

$schedule
     ->route("Northwest Shuttle", "northwest")
     ->summary("Runs weekdays 7AM-6PM, all year round")
     ->except_holidays()
     ->perHour(3)
     ->stops(      
	st("KendallSqT"                ,"kendsq_d" , "nwcamp", '25'),
	st("Amherst/Wadsworth"         ,"amhewads", "nwcamp", '27'),
        st("77MassAve"                 ,"mass77"  , "nwcamp", '30'),
        st("MITMuseum"                 ,"mitmus"  , "nwcamp", '32'),
        st("70Pacific"                 ,"paci70"  , "kendsq", '34'),
        st("Warehouse"                 ,"whou"    , "kendsq", '35'),
        st("Edgarton"                  ,"edge"    , "kendsq", '36'),
        st("Vassar/MassAve"            ,"vassmass", "kendsq", '39'),
        st("Stata"                     ,"statct"  , "kendsq", '41'))
     ->addHours("Mon-Fri",
       hours("7-17 18:1"),
       delay(10, "7-9")
     );  

$schedule
     ->route("Boston Daytime", "boston")
     ->summary("Runs weekdays 8AM-6PM, Sep-May")
     ->except_holidays()
     ->perHour(3)
     ->stops(      
	st("84MassAve."                  ,"mass84_d", "boston"   , '07'),
        st("MassAve/Beacon"              ,"massbeac", "cambridge", '09'),
        st("487CommAve(PSK)"             ,"comm487" , "cambridge", '10'),
        st("64BayState(TXI)"             ,"bays64"  , "cambridge", '11'),
        st("478CommAve."                 ,"comm478" , "cambridge", '14'),
        st("450Beacon"                   ,"beacmass", "cambridge", '19'),
        st("77MassAve"                   ,"mass77"  , "cambridge", '23'))
     ->addHours("Mon-Fri", hours("8-17"));  
?>
