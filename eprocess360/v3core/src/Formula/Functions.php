<?php
namespace eprocess360\v3core\Formula;
use eprocess360\v3modules\Fee\Model\FeeMatrices;
use eprocess360\v3modules\Fee\Model\FeeTemplates;

/**
 * Created by PhpStorm.
 * User: kiranebilak
 * Date: 2/25/16
 * Time: 9:45 AM
 */
class Functions
{
    private $formula;

    /**
     * Functions constructor.
     * @param Formula $formula
     */
    public function __construct(Formula $formula)
    {
        $this->formula = $formula;
    }

    /**
     * @param string $variable Dot-notation version of keydict
     * @return int
     */
    public function _variable($variable)
    {
        $path = explode('.', $variable);
        $keydict = $this->formula->getKeydict(array_shift($path));
        while (sizeof($path)) {
            $next = array_shift($path);
            $keydict = $keydict->{$next};
        }
        return $keydict->get();
    }

    /**
     * @param $function
     * @return bool
     */
    public function _exists($function)
    {
        return method_exists($this, $function);
    }

    /**
     * @param FormulaString|null $feeTemplateName
     * @param null $input
     * @return Float|int|mixed
     */
    public function Matrix(FormulaString $feeTemplateName = null, $input = null)
    {
        $matrixFormula = $this->formula->getEnvironmentVariables('matrixFormula');
        $projectData = $this->formula->getEnvironmentVariables('projectData');
        $idController = $this->formula->getEnvironmentVariables('idController');
        $idProject = $this->formula->getEnvironmentVariables('idProject');
        $quantity = $this->formula->getEnvironmentVariables('quantity');
        $idFeeTemplate = $this->formula->getEnvironmentVariables('idFeeTemplate');

        $result = $feeTemplateName ? FeeTemplates::getMatrixByTitle($feeTemplateName->getValue(), $projectData, $idController, $idProject, $quantity, $input)
            : FeeMatrices::solveMatrix($idFeeTemplate, $this->formula->parse($matrixFormula, false));

        $lastResults = $this->formula->getEnvironmentVariables('lastResults');
        $string = $feeTemplateName ? "Matrix(\"".$feeTemplateName->getValue()."\")":"Matrix()";
        $lastResults[$string] = $result;

        $this->formula->setEnvironmentVariable('lastResults', $lastResults);

        return $result;
    }

    /**
     * @param FormulaString $feeTemplateName
     * @return mixed
     * @throws \Exception
     */
    public function Basis(FormulaString $feeTemplateName = NULL)
    {
        $projectFees = $this->formula->getEnvironmentVariables('projectFees');
        $fixedAmount = $this->formula->getEnvironmentVariables('fixedAmount');
        $result = $feeTemplateName ? FeeTemplates::getBasisByTitle($feeTemplateName->getValue(), $projectFees) : $fixedAmount;

        $lastResults = $this->formula->getEnvironmentVariables('lastResults');
        $string = $feeTemplateName ? "Basis(\"".$feeTemplateName->getValue()."\")":"Basis()";
        $lastResults[$string] = $result;

        $this->formula->setEnvironmentVariable('lastResults', $lastResults);

        return $result;
    }

    /**
     * @param FormulaString $feeTemplateName
     * @return int|mixed
     * @throws \Exception
     */
    public function Fee(FormulaString $feeTemplateName = NULL)
    {
        $projectFees = $this->formula->getEnvironmentVariables('projectFees');
        $result = $feeTemplateName ? FeeTemplates::getTotalByTitle($feeTemplateName->getValue(), $projectFees) : 0;

        $lastResults = $this->formula->getEnvironmentVariables('lastResults');
        $string = $feeTemplateName ? "Fee(\"".$feeTemplateName->getValue()."\")":"Fee()";
        $lastResults[$string] = $result;

        $this->formula->setEnvironmentVariable('lastResults', $lastResults);

        return $result;
    }

    /**
     * @param FormulaString $feeTemplateName
     * @return mixed
     */
    public function Quantity(FormulaString $feeTemplateName = NULL)
    {
        $projectFees = $this->formula->getEnvironmentVariables('projectFees');
        $quantity = $this->formula->getEnvironmentVariables('quantity');
        $result = $feeTemplateName ? FeeTemplates::getQuantityByTitle($feeTemplateName->getValue(), $projectFees) : $quantity;

        $lastResults = $this->formula->getEnvironmentVariables('lastResults');
        $string = $feeTemplateName ? "Quantity(\"".$feeTemplateName->getValue()."\")":"Quantity()";
        $lastResults[$string] = $result;

        $this->formula->setEnvironmentVariable('lastResults', $lastResults);

        return $result;
    }

    /**
     * @param FormulaString $feeTemplateName
     * @return int|mixed
     */
    public function Sum(FormulaString $feeTemplateName = NULL)
    {
        $projectFees = $this->formula->getEnvironmentVariables('projectFees');
        $idController = $this->formula->getEnvironmentVariables('idController');
        $result = $feeTemplateName ? FeeTemplates::getSumByType($feeTemplateName->getValue(), $projectFees, $idController) : 0;

        $lastResults = $this->formula->getEnvironmentVariables('lastResults');
        $string = $feeTemplateName ? "Sum(\"".$feeTemplateName->getValue()."\")":"Sum()";
        $lastResults[$string] = $result;

        $this->formula->setEnvironmentVariable('lastResults', $lastResults);

        return $result;
    }

    public function Env(FormulaString $envVariableName)
    {
        return $this->formula->getEnvironmentVariables($envVariableName->getValue());
    }
}