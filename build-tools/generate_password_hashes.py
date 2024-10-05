from pathlib import Path
import textwrap

from common import phase_generate_password_hashes, phase_read_password_list


##############
# Entrypoint #
##############

def generate_password_hashes(password_list_path, out_path):
    '''
    Output is a Dhall expression of type `PasswordList` with all passwords given
    as hashes; written to `out_path`.

    Args:
        password_list_path: Path to the file containing the password list.
            Input must be a file containing a Dhall expression of type
            `PasswordList` (see also `types.dhall`).
        out_path: Path to the output file where the Dhall expression will be
            written.

    '''

    password_list_path = Path(password_list_path).absolute()


    ###########################
    # Read password list file #
    ###########################

    password_list = phase_read_password_list(password_list_path)


    ############################
    # Generate password hashes #
    ############################

    users_2_hashes = phase_generate_password_hashes(password_list)


    ##########################
    # Write Dhall expression #
    ##########################

    print('Writing to output file...')

    passwords_dhall = ''.join([f', t.assignUser2PasswordHash "{user}" "{hash}"\n' for user, hash in users_2_hashes.items()])

    content = textwrap.dedent(f'''\
        let t = ./DHALL_PACKAGE_PATH
        let T = t.types

        in
        [
    ''')
    content += passwords_dhall
    content += textwrap.dedent(f'''\
        ]
        : T.PasswordList
    ''')

    Path(out_path).absolute().write_text(content)