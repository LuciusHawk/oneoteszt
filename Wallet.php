<?php


class Wallet
{
    private $changes;
    private $papers;

    /**
     * Wallet constructor.
     */
    public function __construct()
    {
        if (isset($_SESSION['wallet'])) {
            $this->getWalletFromSession();
        } else {
            $this->changes = [];
            $this->papers = [];
        }
    }

    private function getWalletFromSession()
    {
        $wallet = json_decode($_SESSION['wallet'], true);
        $this->changes = $wallet['changes'];
        $this->papers = $wallet['papers'];
    }

    /**
     * Disabled function
     */
    private function saveWalletToSession()
    {
        $wallet = [];
        $wallet['changes'] = $this->changes;
        $wallet['papers'] = $this->papers;

        //$_SESSION['wallet'] = json_encode($wallet);
    }

    /**
     * @param $type
     * @return bool
     * @throws InvalidArgumentException
     */
    public function add($type)
    {
        if ($this->validateType($type)) {
            switch ($this->getMaterialType($type)) {
                case 'change':
                    $this->addChange($type);
                    return true;
                    break;
                default:
                    $this->addPaper($type);
                    return true;
                    break;
            }
        }
    }

    /**
     * @param $type
     * @return bool
     * @throws InvalidArgumentException
     */
    private function validateType($type)
    {
        if (array_key_exists($type, $this->getAvailableTypes())) {
            return true;
        } else {
            throw new InvalidArgumentException('InvalidArgumentException: ' . $type);
        }
    }

    /**
     * @return stdClass
     */
    public function getContent()
    {
        $wallet = new stdClass();
        $wallet->change = [];
        foreach ($this->changes as $change => $v) {
            for ($i = 0; $i < $v; $i++) {
                $wallet->change[] = (int)$change;
            }
        }

        $wallet->paper = [];
        foreach ($this->papers as $paper => $v) {
            for ($i = 0; $i < $v; $i++) {
                $wallet->paper[] = (int)$paper;
            }
        }

        return $wallet;
    }

    /**
     * @return int
     */
    public function sum()
    {
        $value = 0;
        foreach ($this->changes as $change => $v) {
            for ($i = 0; $i < $v; $i++) {
                $value += (int)$change;
            }
        }

        foreach ($this->papers as $paper => $v) {
            for ($i = 0; $i < $v; $i++) {
                $value += (int)$paper;
            }
        }

        return (int)$value;
    }

    /**
     * @param stdClass $out
     * @return bool
     * @throws InvalidArgumentException
     */
    public function takeOut(stdClass $out)
    {
        $oChanges = $this->changes;
        $oPapers = $this->papers;
        if (isset($out->change) && !empty($out->change)) {
            $changesFlag = $this->takeOutChanges($out->change);
        } else {
            $changesFlag = true;
        }

        if (isset($out->paper) && !empty($out->paper)) {
            $papersFlag = $this->takeOutPapers($out->paper);
        } else {
            $papersFlag = true;
        }

        if (!$changesFlag || !$papersFlag) {
            $this->changes = $oChanges;
            $this->papers = $oPapers;
        }

        return $changesFlag && $papersFlag ? true : false;
    }

    /**
     * @param int $remove
     * @return bool
     * @throws InvalidArgumentException
     */
    public function removable(int $remove)
    {
        if ($remove <= 0) {
            throw new InvalidArgumentException('InvalidArgumentException:Remove ' . $remove);
        }


        if ($this->sum() > $remove) {
            $content = json_decode(json_encode($this->getContent()), true);
            $content = array_merge($content['change'], $content['paper']);
            rsort($content);
            foreach ($content as $item) {
                if ($item <= $remove && ($this->sum() - $item) != $remove) {
                    $remove -= $item;
                }

            }

            return $remove == 0;

        } else {
            throw new InvalidArgumentException('Too much');

        }
    }

    /**
     * @param array $changes
     * @return bool
     * @throws InvalidArgumentException
     */
    private function takeOutChanges(array $changes)
    {
        $all = count($changes);
        $done = 0;
        foreach ($changes as $type) {
            if ($this->validateType($type)) {
                if (!$this->removeChange($type)) {
                    break;
                }
                $done++;
            }
        }

        return $all == $done;
    }

    /**
     * @param array $papers
     * @return bool
     * @throws InvalidArgumentException
     */
    private function takeOutPapers(array $papers)
    {
        $all = count($papers);
        $done = 0;
        foreach ($papers as $type) {
            if ($this->validateType($type)) {
                if (!$this->removePaper($type)) {
                    break;
                }
                $done++;
            }
        }
        return $all == $done;
    }

    private function getMaterialType($type)
    {
        return $this->getAvailableTypes()[$type];
    }

    private function getAvailableTypes()
    {
        return [
            5 => 'change',
            10 => 'change',
            20 => 'change',
            50 => 'change',
            100 => 'change',
            200 => 'change',
            500 => 'paper',
            1000 => 'paper',
            2000 => 'paper',
            5000 => 'paper',
            10000 => 'paper',
            20000 => 'paper',
        ];
    }

    private function addChange($type)
    {
        if (isset($this->changes[$type])) {
            $this->changes[$type] = $this->changes[$type] + 1;
        } else {
            $this->changes[$type] = 1;
        }
        $this->saveWalletToSession();
    }

    private function removeChange($type)
    {
        if (isset($this->changes[$type]) && !empty($this->changes[$type]) && $this->changes[$type] > 0) {
            $this->changes[$type] = $this->changes[$type] - 1;
            $this->saveWalletToSession();
            return true;
        } else {
            return false;
        }
    }

    private function addPaper($type)
    {
        if (isset($this->papers[$type])) {
            $this->papers[$type] = $this->papers[$type] + 1;
        } else {
            $this->papers[$type] = 1;
        }
        $this->saveWalletToSession();

    }

    private function removePaper($type)
    {
        if (isset($this->papers[$type]) && !empty($this->papers[$type]) && $this->papers[$type] > 0) {
            $this->papers[$type] = $this->papers[$type] - 1;
            $this->saveWalletToSession();
            return true;
        } else {
            return false;
        }
    }
}