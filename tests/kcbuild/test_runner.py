import pytest

from kcbuild.runner import CommandError
from kcbuild.runner import Runner


def test_runs_a_command_and_captures_its_output():
    result = Runner().run(["echo", "hello"], capture=True)

    assert result.returncode == 0
    assert result.stdout == "hello\n"


def test_non_zero_exit_raises_by_default():
    with pytest.raises(CommandError) as excinfo:
        Runner().run(["sh", "-c", "exit 3"])

    assert excinfo.value.result.returncode == 3


def test_non_zero_exit_is_returned_when_check_is_off():
    result = Runner().run(["sh", "-c", "exit 3"], check=False)

    assert result.returncode == 3


def test_extra_environment_is_layered_on_the_process_environment():
    result = Runner().run(
        ["sh", "-c", "echo $KCB_TEST_VALUE:$PATH"],
        env={"KCB_TEST_VALUE": "yes"},
        capture=True,
    )

    value, path = result.stdout.strip().split(":", 1)
    assert value == "yes"
    assert path


def test_stdin_is_passed_to_the_command_but_never_echoed():
    echoed: list[str] = []

    result = Runner(echo=echoed.append).run(
        ["cat"],
        stdin="s3cret",
        capture=True,
    )

    assert result.stdout == "s3cret"
    assert all("s3cret" not in line for line in echoed)
    assert any("cat" in line for line in echoed)


def test_commands_run_in_the_configured_directory(tmp_path):
    result = Runner(cwd=tmp_path).run(["pwd"], capture=True)

    assert result.stdout.strip() == str(tmp_path.resolve())


def test_per_call_directory_overrides_the_default(tmp_path):
    sub = tmp_path / "sub"
    sub.mkdir()

    result = Runner(cwd=tmp_path).run(["pwd"], capture=True, cwd=sub)

    assert result.stdout.strip() == str(sub.resolve())


def test_dry_run_echoes_without_executing():
    echoed: list[str] = []

    result = Runner(dry_run=True, echo=echoed.append).run(
        ["sh", "-c", "exit 1"],
    )

    assert result.returncode == 0
    assert echoed and "exit 1" in echoed[0]


def test_dry_run_still_executes_read_only_queries():
    result = Runner(dry_run=True).run(
        ["echo", "real"],
        capture=True,
        always_run=True,
    )

    assert result.stdout == "real\n"


def test_echo_line_shows_environment_and_command():
    echoed: list[str] = []

    Runner(dry_run=True, echo=echoed.append).run(
        ["docker", "build", "."],
        env={"X": "a b"},
    )

    assert echoed == ["$ X='a b' docker build ."]
