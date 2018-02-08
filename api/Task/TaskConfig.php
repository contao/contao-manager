<?php

namespace Contao\ManagerApi\Task;

class TaskConfig
{
    /**
     * @var string
     */
    private $file;

    /**
     * @var array
     */
    private $data;

    /**
     * Constructor.
     *
     * @param string     $file
     * @param null       $name
     * @param array|null $options
     */
    public function __construct($file, $name = null, array $options = null)
    {
        $this->file = $file;
        $this->data = [
            'name' => $name,
            'options' => $options,
        ];

        if (null === $name && null === $options) {
            $this->data = json_decode(file_get_contents($file), true);

            if (!is_array($this->data)) {
                throw new \RuntimeException(sprintf('Invalid task data in file "%s"', $file));
            }
        }
    }

    public function getName()
    {
        return $this->data['name'];
    }

    public function getOptions()
    {
        return $this->data['options'];
    }

    public function getOption($name, $default = null)
    {
        return isset($this->data['options'][$name]) ? $this->data['options'][$name] : $default;
    }

    public function getStatus()
    {
        return isset($this->data['status']) ? $this->data['status'] : null;
    }

    public function setStatus($status)
    {
        if (isset($this->data['status']) && $this->data['status'] === $status) {
            return;
        }

        $this->data['status'] = $status;

        $this->save();
    }

    public function save()
    {
        file_put_contents(
            $this->file,
            json_encode($this->data),
            LOCK_EX
        );
    }
}
