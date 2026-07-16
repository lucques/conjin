let common = ../../config_common.dhall

let t = common.p.tools
let T = common.p.types
let P = common.p.prelude


------------
-- Config --
------------

let deplName = "dcd"
let deplDir  = common.deploymentsDir ++ "/" ++ deplName
let host     = "conjin.mainlab.site"
let pathBase = ".."
let urlBase  = "/"

let rcloneRemote = {
    , name       = "strato-sftp"
    , dir        = "/conjin"
    , configPath = ./RCLONE_CONFIG_PATH as Text
}

let authentication = T.Authentication::{
    , staticLoginWithoutUserName = True
    , staticUsers2passwords = ./password_list.dhall
}

in

t.makeDefaultRemoteDepl
    deplName
    common.conjinDir
    common.appDir
    deplDir
    authentication
    common.authorization
    False
    common.sharedBareModules
    common.localBareModules
    common.modules
    host
    pathBase
    urlBase
    rcloneRemote
: T.RemoteDepl
