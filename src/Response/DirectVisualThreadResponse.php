<?php

namespace InstagramAPI\Response;

use InstagramAPI\Response;

/**
 * DirectVisualThreadResponse.
 *
 * @method Model\ActionBadge getActionBadge()
 * @method mixed getCanonical()
 * @method bool getHasNewer()
 * @method bool getHasOlder()
 * @method Model\User getInviter()
 * @method mixed getIsPin()
 * @method mixed getIsSpam()
 * @method Model\DirectThreadItem[] getItems()
 * @method mixed getLastActivityAt()
 * @method mixed getLastActivityAtSecs()
 * @method Model\PermanentItem getLastPermanentItem()
 * @method Model\UnpredictableKeys\DirectThreadLastSeenAtUnpredictableContainer getLastSeenAt()
 * @method Model\User[] getLeftUsers()
 * @method mixed getMessage()
 * @method mixed getMuted()
 * @method mixed getNamed()
 * @method mixed getNewestCursor()
 * @method mixed getOldestCursor()
 * @method mixed getPending()
 * @method string getPendingScore()
 * @method string getStatus()
 * @method string getThreadId()
 * @method mixed getThreadTitle()
 * @method mixed getThreadType()
 * @method mixed getUnseenCount()
 * @method Model\User[] getUsers()
 * @method string getViewerId()
 * @method Model\_Message[] get_Messages()
 * @method bool isActionBadge()
 * @method bool isCanonical()
 * @method bool isHasNewer()
 * @method bool isHasOlder()
 * @method bool isInviter()
 * @method bool isIsPin()
 * @method bool isIsSpam()
 * @method bool isItems()
 * @method bool isLastActivityAt()
 * @method bool isLastActivityAtSecs()
 * @method bool isLastPermanentItem()
 * @method bool isLastSeenAt()
 * @method bool isLeftUsers()
 * @method bool isMessage()
 * @method bool isMuted()
 * @method bool isNamed()
 * @method bool isNewestCursor()
 * @method bool isOldestCursor()
 * @method bool isPending()
 * @method bool isPendingScore()
 * @method bool isStatus()
 * @method bool isThreadId()
 * @method bool isThreadTitle()
 * @method bool isThreadType()
 * @method bool isUnseenCount()
 * @method bool isUsers()
 * @method bool isViewerId()
 * @method bool is_Messages()
 * @method $this setActionBadge(Model\ActionBadge $value)
 * @method $this setCanonical(mixed $value)
 * @method $this setHasNewer(bool $value)
 * @method $this setHasOlder(bool $value)
 * @method $this setInviter(Model\User $value)
 * @method $this setIsPin(mixed $value)
 * @method $this setIsSpam(mixed $value)
 * @method $this setItems(Model\DirectThreadItem[] $value)
 * @method $this setLastActivityAt(mixed $value)
 * @method $this setLastActivityAtSecs(mixed $value)
 * @method $this setLastPermanentItem(Model\PermanentItem $value)
 * @method $this setLastSeenAt(Model\UnpredictableKeys\DirectThreadLastSeenAtUnpredictableContainer $value)
 * @method $this setLeftUsers(Model\User[] $value)
 * @method $this setMessage(mixed $value)
 * @method $this setMuted(mixed $value)
 * @method $this setNamed(mixed $value)
 * @method $this setNewestCursor(mixed $value)
 * @method $this setOldestCursor(mixed $value)
 * @method $this setPending(mixed $value)
 * @method $this setPendingScore(string $value)
 * @method $this setStatus(string $value)
 * @method $this setThreadId(string $value)
 * @method $this setThreadTitle(mixed $value)
 * @method $this setThreadType(mixed $value)
 * @method $this setUnseenCount(mixed $value)
 * @method $this setUsers(Model\User[] $value)
 * @method $this setViewerId(string $value)
 * @method $this set_Messages(Model\_Message[] $value)
 * @method $this unsetActionBadge()
 * @method $this unsetCanonical()
 * @method $this unsetHasNewer()
 * @method $this unsetHasOlder()
 * @method $this unsetInviter()
 * @method $this unsetIsPin()
 * @method $this unsetIsSpam()
 * @method $this unsetItems()
 * @method $this unsetLastActivityAt()
 * @method $this unsetLastActivityAtSecs()
 * @method $this unsetLastPermanentItem()
 * @method $this unsetLastSeenAt()
 * @method $this unsetLeftUsers()
 * @method $this unsetMessage()
 * @method $this unsetMuted()
 * @method $this unsetNamed()
 * @method $this unsetNewestCursor()
 * @method $this unsetOldestCursor()
 * @method $this unsetPending()
 * @method $this unsetPendingScore()
 * @method $this unsetStatus()
 * @method $this unsetThreadId()
 * @method $this unsetThreadTitle()
 * @method $this unsetThreadType()
 * @method $this unsetUnseenCount()
 * @method $this unsetUsers()
 * @method $this unsetViewerId()
 * @method $this unset_Messages()
 */
class DirectVisualThreadResponse extends Response
{
    const JSON_PROPERTY_MAP = [
        Model\DirectThread::class, // Import property map.
    ];
}
