## Goal
Offering a interace of streamlining the collection of information from people by offering a swipe interface to answering questions in binary fashion.

## Acceptance criteria (must be testable)

AC1 Registering and login
- Users can register for the service in a view on the url /register. Required data points for registration is name, email and password.
- Users can, after successfull registration, login on a view on the url /login. If the password is incorrect or username not existing the user must be informed about that and offered a new try.

AC2 Building 'a svaip'
- Users can design their 'svaips' with cards in a drag and drop fashion. The user can define conditional flows from card to card based on the answers from the user.
- The cards has two ways of collecting information; swipe right or left. The default answers for right and left is yes and no, but they can be changed.
- A 'svaip' ends with a 'end card' that can contain form elements such as e-mail field and checkboxes and the data collected in the end card must be saved together with the answers from the card.

AC3 Sharing a svaip
- After saving a svaip the user can share it with a simple URL. This URL is accessable by everybody who has it and this URL leads to a svaip run.
- The svaip run is a core functionality of the app and must be design to be mobile-friendly and with accessebility as a guide.
- The swipe motion on the svaip run page must be natural and enjoyable to interact with as well as fast and snappy.

AC4 Viewing the results
- A user can view the results of all the runs of their created svaips.
- The results for a svaip shows up as a list and the user can drill down in specific runs. When the user drills down she can she individual answers for each card, the time the user spent on each card, the time for the complete run and any data collected at the end card.

## Definition of done
- Tests added/updated for all ACs
- scripts/checks.ps1 runs successfully