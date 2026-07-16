let common = ../../config_common.dhall

let t = common.p.tools
let T = common.p.types

let deplName = "test"
let deplDir = common.deploymentsDir ++ "/" ++ deplName
let playwrightTestsDir = ../PLAYWRIGHT_TESTS_DIR as Text

let localDb = t.makeDefaultLocalDb common.appDir deplDir

let db = {
    , user = localDb.user
    , userPassword = localDb.userPassword
    , rootPassword = localDb.rootPassword
    , initFilesDir = localDb.initFilesDir
}: T.TestDb

let store = {
    , initFilesDir = None Text
}: T.TestStore

let authentication = T.Authentication::{
    , staticLoginWithoutUserName = True
    , staticUsers2passwords = [
        , t.assignUser2PasswordHash "root"        "$2y$10$3r69tIVbsoYACoMM81EOrOHGbPNnnsMut98iE8Q1AAIid.jxayy0O"
        , t.assignUser2PasswordHash "admin"       "$2y$10$L8rCMAalV69HKez3FBUm/.n1Rk7izWi.vj6RMztTHm1oy7hSGQBim"
        , t.assignUser2PasswordHash "preprocess"  "$2y$10$bb99UshHmftrekgYrYsJh..gh8e7USa0YG1sn0go0gsTkdmpz0UqC"
        , t.assignUser2PasswordHash "linkchecker" "$2y$10$UYseA1W4jorYyj/sYlMVAO/OhRoAU5/Jd2k7Icz8x925nPqlJXty2"
    ]
}

let localBase = t.makeDefaultLocalDepl
    deplName
    common.conjinDir
    common.appDir
    deplDir
    authentication
    common.authorization
    (Some localDb)
    True
    common.sharedBareModules
    common.localBareModules
    common.modules

in {
    , depl = localBase.depl
    , playwrightTestsDir = Some playwrightTestsDir
    , db = Some db
    , store = Some store
    , preprocessUser = "preprocess"
    , linkchecker = {
        , user = localBase.linkchecker.user
        , excludeTargets = [["module-tests-expect-fail"]]
    }
}: T.TestDepl
