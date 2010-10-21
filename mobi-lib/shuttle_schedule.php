<?php
$docRoot = getenv("DOCUMENT_ROOT");

require_once $docRoot . "/mobi-config/mobi_lib_constants.php";
require_once LIBDIR . "ShuttleSchedule.php";

$schedule = new ShuttleSchedule();

$schedule
     ->route("Cambridge All", "saferidecamball", "SafeRide")
     ->summary("Runs every evening on holidays and summer. No map is available for this route")
     ->perHour(1)
     ->stops(
        st("84 Mass Ave"                    ,"mass84_d"  , "frcamp", '00'),
	st("W4 / McCormick"                 ,"mccrmk"    , "frcamp", '01'),
	st("W51 / Burton"                   ,"burtho"    , "frcamp", '02'),
	st("W70 / New House"                ,"newho"     , "frcamp", '03'),
        st("W85 / Tang / Westgate"          ,"tangwest"  , "frcamp", '04'),
        st("W79 / Simmons"                  ,"simmhl"    , "frcamp", '06'),
        st("WW15"                           ,"ww15"      , "frcamp", '08'),
        st("Brookline @ Chestnut"           ,"brookchest", "frcamp", '09'),
        st("Putnam @ Magazine"              ,"putmag"    , "frcamp", '10'),
        st("River @ Fairmont"               ,"rivfair"   , "frcamp", '12'),
        st("River @ Upton"                  ,"rivpleas"  , "frcamp", '13'),
        st("River @ Franklin"               ,"rivfrank"  , "frcamp", '14'),
        st("Sydney @ Green"                 ,"sydgreen"  , "tocamp", '16'),
        st("NW86 / 70 Pacific"              ,"paci70"    , "tocamp", '18'),
        st("NW30 / Warehouse"               ,"whou"      , "tocamp", '19'),
        st("NW10 / Edgerton"                ,"edge"      , "tocamp", '20'),
        st("84 Mass Ave "                   ,"mass84"    , "frcamp", '30'),
        st("NW10 / Edgerton "               ,"nw10"      , "frcamp", '33'),
        st("NW30 / Warehouse "              ,"nw30"      , "frcamp", '34'),
        st("NW86 / 70 Pacific "             ,"nw86"      , "frcamp", '35'),
        st("NW61 / Random Hall"             ,"randhl"    , "tocamp", '36'),
        st("Main @ Windsor"                 ,"mainwinds" , "tocamp", '39'),
        st("Portland @ Hampshire"           ,"porthamp"  , "tocamp", '40'),
        st("638 Cambridge St"               ,"camb638"   , "tocamp", '43'),
        st("Cambridge @ Fifth"              ,"camb5th"   , "tocamp", '44'),
        st("Sixth @ Charles St"             ,"6thcharl"  , "tocamp", '45'),
        st("East Lot on Main St"            ,"elotmain"  , "tocamp", '47'),
        st("Bld 66 (Ames St)"               ,"amesbld66" , "tocamp", '48'),
        st("MIT Medical"                    ,"mitmed"    , "tocamp", '50'),
        st("Kendall T"                      ,"kendsq"    , "tocamp", '52'),
        st("E40 / Wadsworth"                ,"wadse40"   , "tocamp", '53'),
        st("77 Mass. Ave"                   ,"mass77"    , "tocamp", '56'))
     ->addHours("Thu-Sat", hours("18 19 20 21 22 23 0 1 2")) 
     ->addHours("Sun-Wed", hours("18 19 20 21 22 23 0 1"));

$schedule
     ->route("Boston All", "saferidebostonall", "SafeRide")
     ->summary("Runs every evening on holidays and summer. No map is available for this route")
     ->perHour(1)
     ->stops(
	st("84 Mass Ave"                    ,"mass84_d"   , "comm487", '00'),
        st("Mass Ave @ Beacon"              ,"massbeac"   , "comm487", '02'),
        st("MBTA stop at Newbury"           ,"massnewb"   , "comm487", '04'),
        st("487 Comm Ave"                   ,"comm487"    ,  "mass84", '06'),
        st("478 Comm Ave"                   ,"comm478"    ,  "mass84", '09'),
        st("Mass Ave @ Beacon "             ,"beacmass"   ,  "mass84", '12'),
        st("77 Mass Ave"                    ,"mass77"     ,  "mass84", '14'),
	st("84 Mass Ave "                   ,"mass84"     ,  "manc58", '15'),
        st("Mass Ave @ Beacon  "            ,"massbeac_b" ,  "manc58", '18'),
        st("528 Beacon St"                  ,"beac528"    ,  "manc58", '22'),
        st("487 Comm Ave "                  ,"487comm"    ,  "manc58", '28'),
        st("111 Baystate"                   ,"bays111"    ,  "manc58", '37'),
        st("155 Baystate"                   ,"bays155"    ,  "manc58", '39'),
        st("58 Manchester (ZBT)"            ,"manc58"     ,  "mass84", '43'),
        st("259 St Paul St (ET)"            ,"stpaul259"  ,  "mass84", '46'),
        st("Mass Ave @ Beacon   "           ,"beacmass_a" ,  "mass84", '58'))
     ->addHours("Thu-Sat", hours("18 19 20 21 22 23 0 1 2")) 
     ->addHours("Sun-Wed", hours("18 19 20 21 22 23 0 1"));

$schedule
     ->route("Cambridge East", "saferidecambeast", "SafeRide")
     ->summary("Runs every evening, all year round")
     ->perHour(2)
     ->stops(
	st("84 Mass. Ave"                   ,"mass84_d" , "frcamp", '00'),
        st("NW10 / Edgerton"                ,"nw10"     , "frcamp", '03'),
        st("NW30 / Warehouse"               ,"nw30"     , "frcamp", '04'),
        st("NW86 / 70 Pacific"              ,"nw86"     , "frcamp", '05'),
        st("NW61 / Random Hall"             ,"nw61"     , "frcamp", '06'),
        st("Main St @ Windsor St"           ,"mainwinds", "frcamp", '09'),
        st("Portland St @ Hampshire St"     ,"porthamp" , "frcamp", '10'),
        st("638 Cambridge St"               ,"camb638"  , "frcamp", '13'),
        st("Cambridge St @ Fifth St"        ,"camb5th"  , "frcamp", '14'),
        st("Sixth @ Charles St"             ,"6thcharl" , "tocamp", '15'),
        st("East Lot on Main St"            ,"elotmain" , "tocamp", '17'),
        st("Bld 66 (Ames St)"               ,"amesbld66", "tocamp", '18'),
        st("MIT Medical / 34 Carleton"      ,"mitmed"   , "tocamp", '20'),
        st("Kendall T"                      ,"kendsq"   , "tocamp", '22'),
        st("E40 / Wadsworth"                ,"wadse40"  , "tocamp", '23'),
        st("77 Mass. Ave"                   ,"mass77"   , "tocamp", '26'))    
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
	st("84 Mass. Ave"                   ,"mass84_d"  , "frcamp", '00'),
        st("W4 / McCormick"                 ,"mccrmk"    , "frcamp", '01'),
        st("W51 / Burton"                   ,"burtho"    , "frcamp", '02'),
        st("W70 / New House"                ,"newho"     , "frcamp", '03'),
        st("W85 / Tang / Westgate"          ,"tangwest"  , "frcamp", '04'),
        st("W79 / Simmons"                  ,"simmhl"    , "frcamp", '06'),
        st("WW15 (Request)"                 , NULL       ,  NULL   , '07'),
        st("Brookline St @ Chestnut St"     ,"brookchest", "frcamp", '09'),
        st("Putnam Ave @ Magazine St"       ,"putmag"    , "frcamp", '10'),
        st("River St @ Fairmont St"         ,"rivfair"   , "frcamp", '12'),
        st("River St @ Upton St"            ,"rivpleas"  , "frcamp", '13'),
        st("River St @ Franklin St"         ,"rivfrank"  , "tocamp", '14'),
        st("Sydney @ Green St"              ,"sydgreen"  , "tocamp", '16'),
        st("NW86 / 70 Pacific St"           ,"paci70"    , "tocamp", '18'),
        st("NW30 / Warehouse"               ,"whou"      , "tocamp", '19'),
        st("NW10 / Edgerton"                ,"edge"      , "tocamp", '20'))    
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
	st("84 Mass Ave"                    ,"mass84_d" , "boston", '15'),
        st("Mass Ave @ Beacon St"           ,"massbeac" , "boston", '18'),
        st("528 Beacon St"                  ,"beac528"  , "boston", '19'),
        st("487 Comm Ave"                   ,"comm487"  , "boston", '21'),
        st("64 Baystate"                    ,"bays64"   , "boston", '23'),
        st("111 Baystate"                   ,"bays111"  , "boston", '24'),
        st("155 Baystate"                   ,"bays155"  , "boston", '25'),
        st("259 St Paul St (ET)"            ,"stpaul259", "boston", '32'),
        st("58 Manchester (ZBT)"            ,"manc58"   , "mass84", '34'),
	st("550 Memorial Drive"             ,"memo550"  , "mass84", '40'),
        st("Simmons Hall"                   ,"simmhl"   , "mass84", '41'))
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
	st("84 Mass. Ave"                  ,"mass84_d" ,"boston", '00'),
        st("Mass. Ave / Beacon St"         ,"massbeac" ,"boston", '02'),
        st("478 Comm. Ave"                 ,"comm478"  ,"boston", '04'),
        st("Vanderbilt (Request)"          , NULL      , NULL   , '06'),
        st("28 Fenway"                     ,"fenw28"   ,"boston", '10'),
        st("Prudential Center"             ,"prud"     ,"boston", '12'),
        st("229 Comm Ave"                  ,"comm229"  ,"boston", '15'),
        st("253 Comm Ave"                  ,"comm253"  ,"mass84", '16'),
        st("32 Hereford St"                ,"here32"   ,"mass84", '17'),
        st("450 Beacon St"                 ,"beac450"  ,"mass84", '18'),
        st("Beacon St @ Mass. Ave"         ,"beacmass" ,"mass84", '19'))
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
	st("Kendall Square T"               ,"kendsq_d", "wcamp" , '15'),
        st("Amherst/Wadsworth"              ,"amhewads", "wcamp" , '17'),
        st("Media Lab"                      ,"medilb"  , "wcamp" , '18'),
        st("Building 39"                    ,"build39" , "wcamp" , '20'),
        st("84 Mass Avenue"                 ,"mass84"  , "wcamp" , '22'),
        st("Burton House"                   ,"burtho"  , "wcamp" , '24'),
        st("Tang/Westgate"                  ,"tangwest", "wcamp" , '25'),
        st("W92 @ Amesbury Street"          ,"w92ames",  "kendsq", '26'),
        st("Simmons Hall"                   ,"simmhl"  , "kendsq", '27'),
        st("Vassar/Mass Ave"                ,"vassmass", "kendsq", '29'),
        st("Stata"                          ,"statct"  , "kendsq", '30'))
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
	st("Kendall Square T"          ,"kendsq_d" , "nwcamp", '25'),
	st("Amherst/Wadsworth"         ,"amhewads", "nwcamp", '27'),
        st("77 Mass Avenue"            ,"mass77"  , "nwcamp", '30'),
        st("MIT Museum (N52)"          ,"mitmus"  , "nwcamp", '32'),
        st("70 Pacific Street (NW86)"  ,"paci70"  , "kendsq", '34'),
        st("The Warehouse (NW30)"      ,"whou"    , "kendsq", '35'),
        st("Edgerton (NW10)"           ,"edge"    , "kendsq", '36'),
        st("Vassar/Mass Ave"           ,"vassmass", "kendsq", '39'),
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
	st("84 Mass. Ave."                  ,"mass84_d", "boston"   , '07'),
        st("Mass. Ave. / Beacon"            ,"massbeac", "cambridge", '09'),
        st("487 Comm. Ave. (PSK)"           ,"comm487" , "cambridge", '10'),
        st("64 Bay State (TXI)"             ,"bays64"  , "cambridge", '11'),
        st("478 Comm. Ave."                 ,"comm478" , "cambridge", '14'),
        st("450 Beacon St."                 ,"beacmass", "cambridge", '19'),
        st("77 Mass. Ave."                  ,"mass77"  , "cambridge", '23'))
     ->addHours("Mon-Fri", hours("8-17"));  
?>
