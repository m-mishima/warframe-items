<?php

$publicexporturl = 'http://content.warframe.com/PublicExport/Manifest/';

$itemdatabase = array();
$blueprint = array();
$nodedatabase = array();
$nodesystemdatabase = array();
$nightwavemissiondatabase = array();

function fetchurl( $url ) {

    $curl = curl_init();

    curl_setopt_array( $curl, array(
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FAILONERROR => false,
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_TIMEOUT => 10,
    ) );

    $res = curl_exec( $curl );
    $httpresultcode = curl_getinfo( $curl, CURLINFO_RESPONSE_CODE );

    $en = curl_errno( $curl );
    $em = curl_error( $curl );

    curl_close( $curl );

    if ( $res === false ) { // curl自体のエラー
        echo "curl error code = " . $en . PHP_EOL;
        echo $em . PHP_EOL;
        return false;
    }
    if ( $httpresultcode != 200 ) {
        echo "http result = " . $httpresultcode . PHP_EOL;
        echo $res . PHP_EOL;
        return false;
    }

    return $res;
}

function lzma_decode( $input ) {
    $descriptorspec = array(
        0 => array( "pipe", "r" ),  // stdin pipe
        1 => array( "pipe", "w" ),  // stdout pipe
        2 => array( "pipe", "w" ),  // stdout pipe
    );

    $execfile = "xz --format=lzma --decompress --stdout";
    $cwd = './';
    $env = array();

    $output = '';

    $process = proc_open( $execfile, $descriptorspec, $pipes, $cwd, $env );

    if ( is_resource( $process ) ) {
        fwrite( $pipes[0], $input );
        fclose( $pipes[0] );

        $output = stream_get_contents( $pipes[1] );
        fclose( $pipes[1] );
    }

    $process_exitcode = proc_close( $process );

    return $output;
}

function getpublicexport( $filename ) {
    global $publicexporturl;

    $res = fetchurl( $publicexporturl . $filename );
    if ( $res === false ) return false;

    $res = str_replace( "\r\n", '\u000a', $res );
    $res = str_replace( "\n", '\u000a', $res );
    $res = str_replace( "\r", '', $res );

    $json = json_decode( $res, true );

    return $json;
}

function create_itemdatabase( $arr ) {

    global $itemdatabase, $blueprint;
    global $nodedatabase, $nodesystemdatabase, $nightwavemissiondatabase;

    $indexurl                     = $arr['indexurl'];
    $blueprint_str                = $arr['blueprint_str'];
    $key_str                      = $arr['key_str'];
    $itemdatabasefile             = $arr['itemdatabasefile'];
    $nodedatabasefile             = $arr['nodedatabasefile'];
    $nodesystemdatabasefile       = $arr['nodesystemdatabasefile'];
    $nightwavemissiondatabasefile = $arr['nightwavemissiondatabasefile'];

    $result = fetchurl( $indexurl );
    if ( $result === false ) {
        return false;
    }
    $result = lzma_decode( $result );
    if ( $result === false ) {
        return false;
    }

    $indexlist = explode( "\r\n", $result );

    $itemdatabase = array();
    $blueprint = array();
    $nodedatabase = array();
    $nodesystemdatabase = array();
    $nightwavemissiondatabase = array();

    foreach( $indexlist as $v ) {
        echo "fetching " . $v . PHP_EOL;
        $json = getpublicexport( $v );
        file_put_contents( 'json/' . $v, json_encode( $json, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE ) );
        foreach( $json as $k1 => $v1 ) {
            echo 'category ' . $k1 . PHP_EOL;
            switch( $k1 ) {
            case 'ExportCustoms':
                foreach( $v1 as $v2 ) {
                    // Custom items
                    $uniqueName  = $v2['uniqueName'];
                    $name        = $v2['name'];
                    $codexSecret = $v2['codexSecret'];
                    if ( !isset( $v2['description'] ) ) {
                        // non-description item exists
                        $description = '';
                    } else {
                        $descripton  = $v2['description'];
                    }
                    insert_itemdatabase( $uniqueName, $name );
                }
                break;
            case 'ExportDrones':
                foreach( $v1 as $v2 ) {
                    // extractors
                    $uniqueName         = $v2['uniqueName'];
                    $name               = $v2['name'];
                    $descripton         = $v2['description'];
                    $longdescripton     = isset( $v2['longDescription'] ) ? $v2['longDescription'] : '';
                    $binCount           = $v2['binCount'];
                    $binCapacity        = $v2['binCapacity'];
                    $fillRate           = $v2['fillRate'];
                    $durability         = $v2['durability'];
                    $codexSecret        = $v2['codexSecret'];
                    $capacityMultiplier = $v2['capacityMultiplier'];
                    $specialities       = $v2['specialities'];

                    insert_itemdatabase( $uniqueName, $name );
                }
                break;
            case 'ExportFlavour':
                foreach( $v1 as $v2 ) {
                    // idle motion
                    $uniqueName     = $v2['uniqueName'];
                    $name           = $v2['name'];
                    $descripton     = $v2['description'];
                    $longDescripton = isset( $v2['longDescription'] ) ? $v2['longDescription'] : '';
                    $codexSecret    = $v2['codexSecret'];

                    insert_itemdatabase( $uniqueName, $name );
                }
                break;
            case 'ExportFusionBundles':
                foreach( $v1 as $v2 ) {
                    // fusionbundle. what is this?
                    $uniqueName     = $v2['uniqueName'];
                    $descripton     = $v2['description'];
                    $longDescripton = isset( $v2['longDescription'] ) ? $v2['longDescription'] : '';
                    $codexSecret    = $v2['codexSecret'];
                    $fusionPoints   = $v2['fusionPoints'];
                }
                break;
            case 'ExportGear':
                foreach( $v1 as $v2 ) {
                    // Gear items
                    $uniqueName     = $v2['uniqueName'];
                    $name           = $v2['name'];
                    $descripton     = $v2['description'];
                    $longDescripton = isset( $v2['longDescription'] ) ? $v2['longDescription'] : '';
                    $codexSecret    = $v2['codexSecret'];
                    $parentName     = $v2['parentName'];

                    insert_itemdatabase( $uniqueName, $name );
                }
                break;
            case 'ExportKeys':
                foreach( $v1 as $v2 ) {
                    // keys
                    $uniqueName     = $v2['uniqueName'];
                    $name           = $v2['name'];
                    $descripton     = $v2['description'];
                    $longDescripton = isset( $v2['longDescription'] ) ? $v2['longDescription'] : '';
                    $parentName     = $v2['parentName'];
                    $codexSecret    = $v2['codexSecret'];

                    insert_itemdatabase( $uniqueName, sprintf( $key_str, $name ) );
                }
                break;
            case 'ExportRecipes':
                foreach( $v1 as $v2 ) {
                    // recipes
                    $uniqueName         = $v2['uniqueName'];
                    $resultType         = $v2['resultType'];
                    $buildPrice         = $v2['buildPrice'];
                    $buildTime          = $v2['buildTime'];
                    $skipBuildTimePrice = $v2['skipBuildTimePrice'];
                    $consumeOnUse       = $v2['consumeOnUse'];
                    $num                = $v2['num'];
                    $codexSecret        = $v2['codexSecret'];
                    $ingredients        = $v2['ingredients'];
                    $secretIngredients  = $v2['secretIngredients'];

                    if ( $resultType != "" ) {
                        $blueprint[] = array(
                            'uniqueName' => $uniqueName,
                            'resultType' => $resultType,
                            'num' => $num
                        );
                    }
                }
                break;
            case 'ExportRegions':
                foreach( $v1 as $v2 ) {
                    // nodes
                    $name               = $v2['name'];          // Apollodorus
                    $uniqueName         = $v2['uniqueName'];    // SolNode94
                    $systemIndex        = $v2['systemIndex'];   // 0
                    $systemName         = $v2['systemName'];    // Mercury
                    $nodeType           = $v2['nodeType'];      // 0
                    $masteryReq         = $v2['masteryReq'];    // 0
                    $missionIndex       = $v2['missionIndex'];  // 2
                    $factionIndex       = $v2['factionIndex'];  // 2
                    $minEnemyLevel      = $v2['minEnemyLevel']; // 6
                    $maxEnemyLevel      = $v2['maxEnemyLevel']; // 11
                    $levelOverride      = isset( $v2['levelOverride'] ) ? $v2['levelOverride'] : false; // false
                    insert_nodedatabase( $uniqueName, $name, $systemIndex );
                    insert_nodesystemdatabase( $systemIndex, $systemName );
                }
                break;
            case 'ExportRelicArcane':
                foreach( $v1 as $v2 ) {
                    // relics
                    $uniqueName   = $v2['uniqueName'];
                    $name         = $v2['name'];
                    $codexSecret  = $v2['codexSecret'];
                    // in relics
                    //$descripton   = $v2['description'];
                    //$relicRewards = $v2['relicRewards'];
                    // in arcanes
                    //$rarity       = $v2['rarity'];
                    //$levelStats   = $v2['levelStats'];

                    insert_itemdatabase( $uniqueName, $name );
                }
                break;
            case 'ExportResources':
                foreach( $v1 as $v2 ) {
                    // resources
                    $uniqueName     = $v2['uniqueName'];
                    $name           = $v2['name'];
                    $descripton     = $v2['description'];
                    $longDescripton = isset( $v2['longDescription'] ) ? $v2['longDescription'] : '';
                    $codexSecret    = $v2['codexSecret'];
                    $parentName     = $v2['parentName'];

                    insert_itemdatabase( $uniqueName, $name );
                }
                break;
            case 'ExportSentinels':
                foreach( $v1 as $v2 ) {
                    // sentinels
                    $uniqueName     = $v2['uniqueName'];
                    $name           = $v2['name'];
                    $descripton     = $v2['description'];
                    $longDescripton = isset( $v2['longDescription'] ) ? $v2['longDescription'] : '';
                    $health         = $v2['health'];
                    $shield         = $v2['shield'];
                    $armor          = $v2['armor'];
                    $stamina        = $v2['stamina'];
                    $power          = $v2['power'];
                    $codexSecret    = $v2['codexSecret'];

                    insert_itemdatabase( $uniqueName, $name );
                }
                break;
            case 'ExportSortieRewards':
                foreach( $v1 as $v2 ) {
                    // sortie reward list
                    $rewardName  = $v2['rewardName'];
                    $rarity      = $v2['rarity'];
                    $tier        = $v2['tier'];
                    $itemCount   = $v2['itemCount'];
                    $probability = $v2['probability'];
                }
                break;
            case 'ExportNightwave':
                // nightwave missions and rank rewards
                $affilitationTag = $v1['affilitationTag'];
                $challenges      = $v1['challenges'];
                $rewards         = $v1['rewards'];
                foreach( $challenges as $v2 ) {
                    $uniqueName  = $v2['uniqueName'];  // /Lotus/Types/...
                    $name        = $v2['name'];        // グライダー
                    $description = $v2['description']; // エイムグライド中に|COUNT|体の敵を倒す
                    $standing    = $v2['standing'];    // 1000
                    $required    = $v2['required'];    // 20
                    $description = str_replace( '|COUNT|', '' . $required, $description );
                    insert_nightwavemissiondatabase( $uniqueName, $description );
                }
                foreach( $rewards as $v2 ) {
                    $uniqueName  = $v2['uniqueName'];  // /Lotus/Types/...
                    if ( isset( $v2['name'] ) ) {
                        $name        = $v2['name'];        // INTERMISSION II CRED
                        $description = $v2['description'];
                        insert_itemdatabase( $uniqueName, $name );
                    }
                }
                break;
            case 'ExportUpgrades':
                foreach( $v1 as $v2 ) {
                    // mods
                    $uniqueName  = $v2['uniqueName'];  // /Lotus/Weapons/...
                    $name        = $v2['name'];        // Coiling Viper
                    $polarity    = $v2['polarity'];    // AP_POWER
                    $rarity      = $v2['rarity'];      // RARE
                    $codexSecret = $v2['codexSecret']; // false
                    $baseDrain   = $v2['baseDrain'];   // -2
                    $fusionLimit = $v2['fusionLimit']; // 3
                    // riven mod は以下パラメータ無し
                    //$descripton  = $v2['description']; // [ "Powerful.." ]
                    //$type        = $v2['type'];        // STANCE
                    //$subtype     = $v2['subtype'];     // /Lotus/Weapons/...

                    insert_itemdatabase( $uniqueName, $name );
                }
                break;
            case 'ExportWarframes':
                foreach( $v1 as $v2 ) {
                    // warframes and archwings
                    $uniqueName     = $v2['uniqueName'];      // /Lotus/Powersuits/...
                    $name           = $v2['name'];            // AMESHA
                    $parentName     = $v2['parentName'];      // /Lotus/Types/...
                    $descripton     = $v2['description'];     // Transform into a winged guardian.
                    $longDescripton = isset( $v2['longDescription'] ) ? $v2['longDescription'] : ''; //
                    $health         = $v2['health'];          // 400
                    $shield         = $v2['shield'];          // 200
                    $armor          = $v2['armor'];           // 200
                    $stamina        = $v2['stamina'];         // 150
                    $power          = $v2['power'];           // 200
                    $codexSecret    = $v2['codexSecret'];     // false
                    $masteryReq     = $v2['masteryReq'];      // 0
                    $sprintSpeed    = $v2['sprintSpeed'];     // 1.1
                    $abilities      = $v2['abilities'];       // [ ... }

                    insert_itemdatabase( $uniqueName, $name );
                }
                break;
           case 'ExportWeapons':
                foreach( $v1 as $v2 ) {
                    // weapons
                    $name               = $v2['name'];               // MARA DETRON
                    $uniqueName         = $v2['uniqueName'];         // /Lotus/Weapons/...
                    $codexSecret        = $v2['codexSecret'];        // true
                    // kitgun は以下パラメータ無し
                    //$secondsPerShot     = $v2['secondsPerShot'];     // 0.29999998
                    //$damagePerShot      = $v2['damagePerShot'];      // [ ... ]
                    //$magazineSixe       = $v2['magazineSize'];       // 8
                    //$reloadTime         = $v2['reloadTime'];         // 1.05
                    //$totalDamage        = $v2['totalDamage'];        // 280
                    //$trigger            = $v2['trigger'];            // SEMI
                    //$descripton         = $v2['description'];        // For Orokin-era ...
                    //$accuracy           = $v2['accuracy'];           // 13.333333
                    //$critinalChance     = $v2['criticalChance'];     // 0.079999998
                    //$critinalMultiplier = $v2['criticalMultiplier']; // 1.5
                    //$procChance         = $v2['procChance'];         // 0.31999999
                    //$fireRate           = $v2['procChance'];         // 3.3333335
                    //$chargeAttack       = $v2['chargeAttack'];       // 0
                    //$spinAttack         = $v2['spinAttack'];         // 0
                    //$leapAttack         = $v2['leapAttack'];         // 0
                    //$wallAttack         = $v2['wallAttack'];         // 0
                    //$slot               = $v2['slot'];               // 0
                    //$noise              = $v2['noise'];              // ALARMING
                    //$sentinel           = $v2['sentinel'];           // false
                    //$masteryReq         = $v2['masteryReq'];         // 9
                    //$omegaAttenuation   = $v2['omegaAttenuation'];   // 1

                    insert_itemdatabase( $uniqueName, $name );
                }
                break;
            case 'Manifest':
                foreach( $v1 as $v2 ) {
                    // texture images
                    $uniqueName      = $v2['uniqueName'];
                    $textureLocation = $v2['textureLocation'];
                }
                break;
            default:
                echo "unknown category " . $k1 . PHP_EOL;
                break;
            }
        }
    }
    foreach( $blueprint as $v ) {
        $uniqueName = $v['uniqueName'];
        $parentName = $v['resultType'];
        $num        = $v['num'];
        if ( isset( $itemdatabase[ $parentName ] ) ) {
            $name = $itemdatabase[ $parentName ];
        } else {
            echo "undefined builded item " . $parentName . PHP_EOL;
            $name = $parentName;
        }
        if ( $num > 1 ) {
            // multiple, ex. "10 x CIPHER"
            $name = sprintf( "%d x %s", $num, $name );
        }
        $name = sprintf( $blueprint_str, $name );
        insert_itemdatabase( $uniqueName, $name );
    }

    ksort( $itemdatabase );
    ksort( $nodedatabase, SORT_NATURAL );
    ksort( $nodesystemdatabase );
    ksort( $nightwavemissiondatabase );

    file_put_contents( $itemdatabasefile, json_encode( $itemdatabase, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE ) );
    file_put_contents( $nodedatabasefile, json_encode( $nodedatabase, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE ) );
    file_put_contents( $nodesystemdatabasefile, json_encode( $nodesystemdatabase, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE ) );
    file_put_contents( $nightwavemissiondatabasefile, json_encode( $nightwavemissiondatabase, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE ) );

}

function insert_itemdatabase( $key, $value ) {
    global $itemdatabase;
    if ( !isset( $itemdatabase[ $key ] ) ) {
        $itemdatabase[ $key ] = $value;
    } else {
        echo "duplicate name " . $key . PHP_EOL;
    }
}

function insert_nodedatabase( $key, $value, $group ) {
    global $nodedatabase;
    if ( !isset( $nodedatabase[ $key ] ) ) {
        $nodedatabase[ $key ] = $value;
    } else {
        echo "duplicate name " . $key . PHP_EOL;
    }
}

function insert_nodesystemdatabase( $key, $value ) {
    global $nodesystemdatabase;
    $nodesystemdatabase[ $key ] = $value;
}

function insert_nightwavemissiondatabase( $key, $value ) {
    global $nightwavemissiondatabase;
    if ( !isset( $nightwavemissiondatabase[ $key ] ) ) {
        $nightwavemissiondatabase[ $key ] = $value;
    } else {
        echo "duplicate name " . $key . PHP_EOL;
    }
}


function main() {
    create_itemdatabase( array(
        'indexurl' => 'http://content.warframe.com/PublicExport/index_en.txt.lzma',
        'blueprint_str' => '%s BLUEPRINT',
        'key_str'       => '%s KEY',
        'itemdatabasefile'             => 'itemdatabase_en.json',
        'nodedatabasefile'             => 'nodedatabase_en.json',
        'nodesystemdatabasefile'       => 'nodesystemdatabase_en.json',
        'nightwavemissiondatabasefile' => 'nightwavemissiondatabase_en.json'
    ) );
    create_itemdatabase( array(
        'indexurl' => 'http://content.warframe.com/PublicExport/index_ja.txt.lzma',
        'blueprint_str' => '%sの設計図',
        'key_str'       => '%s キー',
        'itemdatabasefile'             => 'itemdatabase_ja.json',
        'nodedatabasefile'             => 'nodedatabase_ja.json',
        'nodesystemdatabasefile'       => 'nodesystemdatabase_ja.json',
        'nightwavemissiondatabasefile' => 'nightwavemissiondatabase_ja.json'
    ) );
}

main();
?>
