let common = ../../config_common.dhall

let t = common.p.tools
let T = common.p.types

let deplName = "conjin-test-web"
let deplDir = common.deploymentsDir ++ "/" ++ deplName

let db = t.makeDefaultLocalDb common.appDir deplDir

let authentication = T.Authentication::{
    , staticLoginWithoutUserName = True
    , staticUsers2passwords = [
        , t.assignUser2Password "root"        "asdf"
        , t.assignUser2Password "admin"       "admin"
        , t.assignUser2Password "preprocess"  "preprocess"
        , t.assignUser2Password "linkchecker" "linkchecker"
    ]
}

let deployment = t.makeDefaultLocalDepl
    deplName
    common.conjinDir
    common.appDir
    deplDir
    authentication
    common.authorization
    (Some db)
    True
    common.sharedBareModules
    common.localBareModules
    common.modules

in

(deployment // {
    , linkchecker = {
        , user = deployment.linkchecker.user
        , excludeTargets = [["module-tests-expect-fail"]]
    }
})
: T.LocalDepl
